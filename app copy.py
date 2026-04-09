import os
import re
import cv2
import numpy as np
from flask import Flask, request, jsonify
import tempfile

# Disable OneDNN/MKL-DNN backend to prevent ConvertPirAttribute2RuntimeAttribute error
os.environ['FLAGS_use_mkldnn'] = '0'
os.environ['FLAGS_mkldnn_cache_capacity'] = '0'

# ===============================
# IMPORT PADDLE OCR
# ===============================
from paddleocr import PaddleOCR

# init sekali (jangan di dalam function biar cepat)
ocr = PaddleOCR(use_textline_orientation=True, lang='en', enable_mkldnn=False)

app = Flask(__name__)

# ==============================================================================
# BLUR CHECK
# ==============================================================================

def is_image_too_blurry(image_path, threshold=40):
    img = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
    if img is None:
        return True, 0.0
    score = cv2.Laplacian(img, cv2.CV_64F).var()
    return score < threshold, round(score, 2)


# ==============================================================================
# PERSPECTIVE CORRECTION (TETAP DIPAKAI)
# ==============================================================================

def order_points(pts):
    rect = np.zeros((4, 2), dtype='float32')
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]
    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)]
    rect[3] = pts[np.argmax(diff)]
    return rect


def correct_perspective(img):
    h, w = img.shape[:2]
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    blur = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blur, 30, 100)

    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
    edges = cv2.dilate(edges, kernel, iterations=1)

    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    contours = sorted(contours, key=cv2.contourArea, reverse=True)

    for cnt in contours[:5]:
        area = cv2.contourArea(cnt)
        if area < w * h * 0.15:
            continue

        peri = cv2.arcLength(cnt, True)
        approx = cv2.approxPolyDP(cnt, 0.02 * peri, True)

        if len(approx) == 4:
            pts = approx.reshape(4, 2).astype('float32')
            rect = order_points(pts)
            tl, tr, br, bl = rect

            width = int(max(np.linalg.norm(br - bl), np.linalg.norm(tr - tl)))
            height = int(width * 54 / 86)

            dst = np.array([
                [0, 0],
                [width - 1, 0],
                [width - 1, height - 1],
                [0, height - 1]
            ], dtype='float32')

            M = cv2.getPerspectiveTransform(rect, dst)
            return cv2.warpPerspective(img, M, (width, height))

    return img


# ==============================================================================
# 🔥 PREPROCESS (DISEDERHANAKAN - BIAR PADDLE OPTIMAL)
# ==============================================================================

def preprocess_image(img):
    img = correct_perspective(img)

    # cukup upscale ringan (Paddle sudah pintar)
    h, w = img.shape[:2]
    img = cv2.resize(img, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)

    return img


# ==============================================================================
# 🔥 OCR PADDLE (CORE UPGRADE)
# ==============================================================================

def ocr_paddle(img):
    img = preprocess_image(img)

    result = ocr.predict(img)

    if not result:
        return ""

    texts = []
    for res in result:
        if res is None:
            continue
        rec_texts = res.get('rec_texts', [])
        for t in rec_texts:
            if t:
                texts.append(t)

    return " ".join(texts)


# ==============================================================================
# CLEAN TEXT (TETAP DIPAKAI)
# ==============================================================================

def clean_text(text):
    text = text.upper()

    text = re.sub(r'TEMPAT\s*/?\s*TGL\.?\s*LAHIR', 'TEMPAT TGL LAHIR', text)
    text = re.sub(r'KEL\.?\s*/?\s*DESA', 'KEL DESA', text)
    text = re.sub(r'RT\s*/?\s*RW|RTIRW|RT IRW', 'RT/RW', text)

    text = re.sub(r'[^\w\s:/\-.,]', ' ', text)
    text = re.sub(r'\s+', ' ', text)

    return text.strip()


# ==============================================================================
# PARSER (TETAP)
# ==============================================================================

_LABELS = [
    'NIK', 'NAMA', 'TEMPAT TGL LAHIR', 'JENIS KELAMIN',
    'ALAMAT', 'RT/RW', 'KEL DESA', 'KECAMATAN',
    'AGAMA', 'STATUS PERKAWINAN', 'PEKERJAAN',
    'KEWARGANEGARAAN', 'BERLAKU HINGGA',
]


def extract_field(text, label):
    others = [re.escape(l) for l in _LABELS if l != label]
    lookahead = '|'.join(others)
    pattern = rf'{re.escape(label)}\s*[:\-]?\s*(.+?)(?=\s*(?:{lookahead})|$)'
    m = re.search(pattern, text)
    return m.group(1).strip() if m else None


# ==============================================================================
# CLEAN FIELD VALUE (noise remover)
# ==============================================================================

_KOTA_PATTERN = (
    r'JAKARTA(\s+(BARAT|TIMUR|SELATAN|UTARA|PUSAT))?|'
    r'BANDUNG|SURABAYA|MEDAN|MAKASSAR|SEMARANG|DEPOK|'
    r'BEKASI|TANGERANG|BOGOR|PALEMBANG|PEKANBARU|MALANG|'
    r'DENPASAR|YOGYAKARTA|BATAM|BANJARMASIN|MANADO|PADANG|'
    r'PONTIANAK|BALIKPAPAN|SAMARINDA|MATARAM|KUPANG|AMBON'
)

def clean_field_value(value):
    if not value:
        return None

    # Hapus trailing tanggal (misal: 02-12-2012)
    value = re.sub(r'\s+\d{2}[-/]\d{2}[-/]\d{4}.*$', '', value)

    # Hapus trailing kota (muncul dari cap KTP)
    value = re.sub(rf'\s+({_KOTA_PATTERN})\s*$', '', value, flags=re.IGNORECASE)

    # Hapus trailing noise: GOL DARAH, angka random, simbol
    value = re.sub(r'\s+GOL\.?\s*DARAH\s*:?\s*[A-B]?[+-]?\s*$', '', value, flags=re.IGNORECASE)
    value = re.sub(r'\s{2,}', ' ', value)
    value = value.strip(' :-')

    return value if value else None


# ==============================================================================
# PARSE KTP (LENGKAP & ROBUST)
# ==============================================================================

def parse_ktp(text):
    result = {k: None for k in [
        "nik", "nama", "tempat_lahir", "tanggal_lahir",
        "jenis_kelamin", "gol_darah", "alamat", "rt_rw",
        "kel_desa", "kecamatan", "agama",
        "status_perkawinan", "pekerjaan",
        "kewarganegaraan", "berlaku_hingga"
    ]}

    # ------------------------------------------------------------------
    # NIK — 16 digit
    # ------------------------------------------------------------------
    nik = re.search(r'\b(\d{16})\b', text)
    if nik:
        result["nik"] = nik.group(1)

    # ------------------------------------------------------------------
    # NAMA
    # ------------------------------------------------------------------
    nama = clean_field_value(extract_field(text, 'NAMA'))
    # Buang suffix noise yang sering ikut (misal: "MIRA SETIAWAN TEMPAT")
    if nama:
        nama = re.sub(r'\s+(TEMPAT|TGL|LAHIR|JENIS|KELAMIN).*$', '', nama, flags=re.IGNORECASE).strip()
        result["nama"] = nama

    # ------------------------------------------------------------------
    # TEMPAT & TANGGAL LAHIR
    # ------------------------------------------------------------------
    ttl_raw = extract_field(text, 'TEMPAT TGL LAHIR')
    if ttl_raw:
        # Coba pisah berdasarkan koma: "JAKARTA, 18-02-1986"
        parts = ttl_raw.split(',', 1)
        if len(parts) == 2:
            result["tempat_lahir"] = parts[0].strip()
            # Ambil tanggal dari bagian kanan
            tgl = re.search(r'\d{2}[-/]\d{2}[-/]\d{4}', parts[1])
            result["tanggal_lahir"] = tgl.group(0).replace('/', '-') if tgl else parts[1].strip()
        else:
            # Fallback: cari tanggal langsung di raw
            tgl = re.search(r'\d{2}[-/]\d{2}[-/]\d{4}', ttl_raw)
            if tgl:
                result["tanggal_lahir"] = tgl.group(0).replace('/', '-')
                result["tempat_lahir"] = ttl_raw[:ttl_raw.index(tgl.group(0))].strip(' ,-')
            else:
                result["tempat_lahir"] = clean_field_value(ttl_raw)
    else:
        # Fallback: cari tanggal di raw_text secara langsung
        tgl_matches = re.findall(r'\d{2}[-/]\d{2}[-/]\d{4}', text)
        if tgl_matches:
            # Tanggal lahir biasanya muncul lebih awal dari berlaku_hingga
            result["tanggal_lahir"] = tgl_matches[0].replace('/', '-')

    # ------------------------------------------------------------------
    # JENIS KELAMIN
    # ------------------------------------------------------------------
    if re.search(r'\bPEREMPUAN\b', text):
        result["jenis_kelamin"] = "PEREMPUAN"
    elif re.search(r'\bLAKI[-\s]LAKI\b', text):
        result["jenis_kelamin"] = "LAKI-LAKI"

    # ------------------------------------------------------------------
    # GOL DARAH (bonus field)
    # ------------------------------------------------------------------
    gol = re.search(r'GOL\.?\s*DARAH\s*[:\-]?\s*([ABO]{1,2}[+-]?)', text, re.IGNORECASE)
    if gol:
        result["gol_darah"] = gol.group(1).upper()

    # ------------------------------------------------------------------
    # ALAMAT
    # ------------------------------------------------------------------
    alamat = clean_field_value(extract_field(text, 'ALAMAT'))
    if alamat:
        # Buang RT/RW yang ikut terbaca
        alamat = re.sub(r'\s+RT\s*/?\s*RW.*$', '', alamat, flags=re.IGNORECASE).strip()
        result["alamat"] = alamat

    # ------------------------------------------------------------------
    # RT/RW
    # ------------------------------------------------------------------
    rtrw = extract_field(text, 'RT/RW')
    if rtrw:
        # Normalisasi: ambil pola NNN/NNN saja
        m = re.search(r'(\d{1,4})\s*/\s*(\d{1,4})', rtrw)
        result["rt_rw"] = f"{m.group(1).zfill(3)}/{m.group(2).zfill(3)}" if m else clean_field_value(rtrw)

    # ------------------------------------------------------------------
    # KEL/DESA
    # ------------------------------------------------------------------
    result["kel_desa"] = clean_field_value(extract_field(text, 'KEL DESA'))

    # ------------------------------------------------------------------
    # KECAMATAN
    # ------------------------------------------------------------------
    result["kecamatan"] = clean_field_value(extract_field(text, 'KECAMATAN'))

    # ------------------------------------------------------------------
    # AGAMA
    # ------------------------------------------------------------------
    agama_raw = clean_field_value(extract_field(text, 'AGAMA'))
    if agama_raw:
        # Normalisasi ke nilai yang valid
        agama_valid = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDHA', 'BUDDHA', 'KONGHUCU']
        for ag in agama_valid:
            if ag in agama_raw.upper():
                result["agama"] = ag
                break
        if not result["agama"]:
            result["agama"] = agama_raw

    # ------------------------------------------------------------------
    # STATUS PERKAWINAN
    # ------------------------------------------------------------------
    status_raw = clean_field_value(extract_field(text, 'STATUS PERKAWINAN'))
    if status_raw:
        status_valid = ['BELUM KAWIN', 'KAWIN', 'CERAI HIDUP', 'CERAI MATI']
        matched = None
        for s in status_valid:
            if s in status_raw.upper():
                matched = s
                break
        result["status_perkawinan"] = matched or status_raw

    # ------------------------------------------------------------------
    # PEKERJAAN
    # ------------------------------------------------------------------
    pekerjaan = clean_field_value(extract_field(text, 'PEKERJAAN'))
    if pekerjaan:
        # Buang noise kota yang ikut terbaca dari cap
        pekerjaan = re.sub(rf'\s+({_KOTA_PATTERN}).*$', '', pekerjaan, flags=re.IGNORECASE).strip()
        result["pekerjaan"] = pekerjaan if pekerjaan else None

    # ------------------------------------------------------------------
    # KEWARGANEGARAAN
    # ------------------------------------------------------------------
    if re.search(r'\bWNA\b', text):
        result["kewarganegaraan"] = "WNA"
    elif re.search(r'\bWNI\b', text):
        result["kewarganegaraan"] = "WNI"

    # ------------------------------------------------------------------
    # BERLAKU HINGGA
    # ------------------------------------------------------------------
    berlaku_raw = extract_field(text, 'BERLAKU HINGGA')
    if berlaku_raw:
        if re.search(r'SEUMUR\s*HIDUP', berlaku_raw, re.IGNORECASE):
            result["berlaku_hingga"] = "SEUMUR HIDUP"
        else:
            tgl = re.search(r'\d{2}[-/]\d{2}[-/]\d{4}', berlaku_raw)
            result["berlaku_hingga"] = tgl.group(0).replace('/', '-') if tgl else clean_field_value(berlaku_raw)

    return result

# ==============================================================================
# ROUTE API
# ==============================================================================

@app.route('/extract', methods=['POST'])
def extract():
    if 'foto' not in request.files:
        return jsonify({"success": False, "error": "Foto tidak ditemukan"}), 400

    file = request.files['foto']

    with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
        file.save(tmp.name)
        tmp_path = tmp.name

    try:
        is_blurry, blur_score = is_image_too_blurry(tmp_path)

        img = cv2.imread(tmp_path)
        if img is None:
            return jsonify({"success": False, "error": "Gagal membaca gambar"}), 422

        raw_ocr = ocr_paddle(img)

        if not raw_ocr.strip():
            return jsonify({
                "success": False,
                "error": "OCR gagal membaca teks",
                "blur_score": blur_score
            }), 422

        text = clean_text(raw_ocr)
        data = parse_ktp(text)

        return jsonify({
            "success": True,
            "data": data,
            "blur_score": float(blur_score),
            "is_blurry": bool(is_blurry),
            "raw_text": text
        })

    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)


@app.route('/health')
def health():
    return jsonify({"status": "ok"})


if __name__ == '__main__':
    app.run(debug=True)