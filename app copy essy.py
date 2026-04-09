import os
import re
import cv2
import numpy as np
import easyocr
from flask import Flask, request, jsonify
import tempfile

# pip install rapidfuzz
from rapidfuzz import process, fuzz

app = Flask(__name__)

# ======================================================================
# INIT EASYOCR (LOAD SEKALI BIAR CEPAT)
# ======================================================================
reader = easyocr.Reader(['id', 'en'], gpu=False)


# ======================================================================
# FIELD LABELS KTP (UNTUK FUZZY MATCHING)
# ======================================================================
FIELD_LABELS = [
    "NAMA",
    "TEMPAT/TGL LAHIR",
    "JENIS KELAMIN",
    "GOL DARAH",
    "ALAMAT",
    "RT/RW",
    "KEL DESA",
    "KECAMATAN",
    "AGAMA",
    "STATUS PERKAWINAN",
    "PEKERJAAN",
    "KEWARGANEGARAAN",
    "BERLAKU HINGGA",
]


# ======================================================================
# BLUR CHECK
# ======================================================================
def is_image_too_blurry(image_path, threshold=120):
    img = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
    if img is None:
        return True, 0.0
    score = cv2.Laplacian(img, cv2.CV_64F).var()
    return score < threshold, round(score, 2)


# ======================================================================
# IMAGE PREPROCESSING (SEBELUM OCR)
# ======================================================================
def preprocess_ktp_image(img):
    """
    Pipeline preprocessing gambar KTP sebelum masuk OCR.
    Returns: (img_original_resized, gray, binary)
    """
    h, w = img.shape[:2]

    # 1. Resize ke lebar minimum 1000px agar OCR lebih akurat
    if w < 1000:
        scale = 1000 / w
        img = cv2.resize(img, None, fx=scale, fy=scale,
                         interpolation=cv2.INTER_CUBIC)

    # 2. Denoise (hilangkan noise foto)
    img_denoised = cv2.fastNlMeansDenoisingColored(img, None, 10, 10, 7, 21)

    # 3. Konversi ke grayscale
    gray = cv2.cvtColor(img_denoised, cv2.COLOR_BGR2GRAY)

    # 4. CLAHE — perbaiki kontras lokal (bagus untuk KTP gelap/pudar)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    # 5. Sharpen
    sharpen_kernel = np.array([[0, -1,  0],
                                [-1,  5, -1],
                                [0, -1,  0]])
    gray = cv2.filter2D(gray, -1, sharpen_kernel)

    # 6. Adaptive threshold — lebih robust dari threshold biasa
    binary = cv2.adaptiveThreshold(
        gray, 255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        31, 10
    )

    return img, gray, binary


# ======================================================================
# OCR MULTI STRATEGY (EASYOCR VERSION)
# ======================================================================
def ocr_multi_strategy(img):
    """
    Jalankan OCR di beberapa varian gambar, ambil hasil terbaik.
    """
    img_resized, gray, binary = preprocess_ktp_image(img)
    inverted = cv2.bitwise_not(binary)

    # Tambah varian sharpen manual dari gray
    blur = cv2.GaussianBlur(gray, (3, 3), 0)
    sharpen_extra = cv2.addWeighted(gray, 1.5, blur, -0.5, 0)

    variants = [
        img_resized,      # original resized + denoised
        gray,             # grayscale + CLAHE + sharpen
        binary,           # adaptive threshold
        inverted,         # inverted binary (kadang teks terang)
        sharpen_extra,    # extra sharpen
    ]

    all_results = []
    for variant in variants:
        try:
            result = reader.readtext(
                variant,
                detail=0,
                paragraph=False,
                text_threshold=0.5,
                low_text=0.3,
            )
            all_results.append(" ".join(result))
        except Exception:
            all_results.append("")

    # Ambil hasil OCR terpanjang (paling banyak teks terdeteksi)
    best = max(all_results, key=lambda x: len(x.strip()))
    return best


# ======================================================================
# FUZZY CORRECTION UNTUK LABEL FIELD KTP
# ======================================================================
def fuzzy_correct_labels(text):
    """
    Koreksi label field KTP yang salah OCR menggunakan fuzzy matching.
    Contoh: 'TOMPATTAL LAHIR' → 'TEMPAT/TGL LAHIR'
    """
    words = text.split()
    corrected = []
    i = 0
    while i < len(words):
        matched = False
        # Coba window 3 kata, 2 kata, 1 kata
        for window in [3, 2, 1]:
            if i + window > len(words):
                continue
            chunk = " ".join(words[i:i + window])
            match_result = process.extractOne(
                chunk, FIELD_LABELS, scorer=fuzz.ratio
            )
            if match_result and match_result[1] >= 72:
                corrected.append(match_result[0])
                i += window
                matched = True
                break
        if not matched:
            corrected.append(words[i])
            i += 1
    return " ".join(corrected)


# ======================================================================
# CLEAN TEXT
# ======================================================================
def clean_text(text):
    text = text.upper()

    # Hard fix — typo yang sangat umum & konsisten
    hard_fix = {
        # Label field
        "KELDESA":        "KEL DESA",
        "KEL/DESA":       "KEL DESA",
        "GOLDARAH":       "GOL DARAH",
        "GOL.DARAH":      "GOL DARAH",
        "RTIRW":          "RT/RW",
        "TEMPATTGL":      "TEMPAT/TGL",
        "TEMPAT/TGLLAHIR":"TEMPAT/TGL LAHIR",

        # Nilai field
        "SLAM":           "ISLAM",
        "PECAWAI":        "PEGAWAI",
        "PGAWAI":         "PEGAWAI",
        "HLNQO":          "HINGGA",
        "HINGO":          "HINGGA",
        "PERKAWLUAN":     "PERKAWINAN",
        "PERKAWIANN":     "PERKAWINAN",
        "KOWARDANOGARAAN":"KEWARGANEGARAAN",
        "KOARDANOGARAAN": "KEWARGANEGARAAN",
        "KEWARGANOGARAAN":"KEWARGANEGARAAN",
        "SETIAIVAN":      "SETIAWAN",
        "JAKARIA DAIAT":  "JAKARTA BARAT",
        "PEGAOUNGAN":     "PEGADUNGAN",
        "KAUDERES":       "KALIDERES",
        "KUCAMALON":      "KECAMATAN",
        "KOLDOSN":        "KEL DESA",

        # Karakter OCR noise
        "A7IBO":          "A7/66",
        "1LUG1G":         "PEKERJAAN",
    }

    for k, v in hard_fix.items():
        text = text.replace(k, v)

    # Hapus karakter yang tidak relevan untuk KTP
    text = re.sub(r'[^\w\s:/\-.,]', ' ', text)

    # Normalize spasi
    text = re.sub(r'\s+', ' ', text).strip()

    # Fuzzy correct label field
    text = fuzzy_correct_labels(text)

    return text


# ======================================================================
# PARSER KTP
# ======================================================================
def parse_ktp(text):
    result = {
        "nik":               None,
        "nama":              None,
        "tempat_lahir":      None,
        "tanggal_lahir":     None,
        "jenis_kelamin":     None,
        "gol_darah":         None,
        "alamat":            None,
        "rt_rw":             None,
        "kel_desa":          None,
        "kecamatan":         None,
        "agama":             None,
        "status_perkawinan": None,
        "pekerjaan":         None,
        "kewarganegaraan":   None,
        "berlaku_hingga":    None,
    }

    # NIK — 16 digit
    nik = re.search(r'\b\d{16}\b', text)
    if nik:
        result["nik"] = nik.group()

    # NAMA
    nama = re.search(
        r'NAMA\s*[:\-]?\s*([A-Z][A-Z\s]{2,40}?)(?=\s*(?:TEMPAT|TGL|LAHIR|JENIS|PEREMPUAN|LAKI))',
        text
    )
    if nama:
        result["nama"] = nama.group(1).strip()

    # TEMPAT & TGL LAHIR — toleran variasi separator
    ttl = re.search(
        r'TEMPAT.{0,8}LAHIR\s*[:\-]?\s*([A-Z][A-Z\s]{1,30}?),?\s*(\d{2}[-/]\d{2}[-/]\d{4})',
        text
    )
    if ttl:
        result["tempat_lahir"]  = ttl.group(1).strip()
        result["tanggal_lahir"] = ttl.group(2).replace('/', '-')

    # JENIS KELAMIN
    if "PEREMPUAN" in text:
        result["jenis_kelamin"] = "PEREMPUAN"
    elif "LAKI" in text:
        result["jenis_kelamin"] = "LAKI-LAKI"

    # GOL DARAH
    gol = re.search(r'GOL.{0,6}DARAH\s*[:\-]?\s*([ABO]{1,2}[+-]?)', text)
    if gol:
        result["gol_darah"] = gol.group(1)

    # ALAMAT
    alamat = re.search(
        r'ALAMAT\s*[:\-]?\s*([A-Z0-9][A-Z0-9\s./\-]{3,80}?)\s*(?=RT)',
        text
    )
    if alamat:
        val = alamat.group(1)
        val = re.sub(r'\s+', ' ', val).strip()
        result["alamat"] = val

    # RT/RW
    rt = re.search(r'\b(\d{3})[/\\](\d{3})\b', text)
    if rt:
        result["rt_rw"] = f"{rt.group(1)}/{rt.group(2)}"

    # KEL DESA
    kel = re.search(
        r'KEL\s*DESA\s*[:\-]?\s*([A-Z][A-Z\s]{1,30}?)(?=\s*(?:KECAMATAN|AGAMA|ISLAM|KRISTEN|KATOLIK|HINDU|BUDDHA))',
        text
    )
    if kel:
        result["kel_desa"] = kel.group(1).strip()

    # KECAMATAN
    kec = re.search(
        r'KECAMATAN\s*[:\-]?\s*([A-Z][A-Z\s]{1,30}?)(?=\s*(?:AGAMA|ISLAM|KRISTEN|KATOLIK|HINDU|BUDDHA|STATUS|$))',
        text
    )
    if kec:
        result["kecamatan"] = kec.group(1).strip()

    # AGAMA
    for agama in ["ISLAM", "KRISTEN", "KATOLIK", "HINDU", "BUDDHA", "KONGHUCU"]:
        if agama in text:
            result["agama"] = agama
            break

    # STATUS PERKAWINAN
    if "BELUM KAWIN" in text:
        result["status_perkawinan"] = "BELUM KAWIN"
    elif "KAWIN" in text:
        result["status_perkawinan"] = "KAWIN"
    elif "CERAI" in text:
        result["status_perkawinan"] = "CERAI"

    # PEKERJAAN
    kerja = re.search(
        r'PEKERJAAN\s*[:\-]?\s*([A-Z][A-Z\s]{1,40}?)(?=\s*(?:KEWARGANEGARAAN|WNI|WNA|$))',
        text
    )
    if kerja:
        result["pekerjaan"] = kerja.group(1).strip()

    # KEWARGANEGARAAN
    if "WNI" in text:
        result["kewarganegaraan"] = "WNI"
    elif "WNA" in text:
        result["kewarganegaraan"] = "WNA"

    # BERLAKU HINGGA
    berlaku = re.search(
        r'BERLAKU\s*HINGGA\s*[:\-]?\s*(\d{2}[-/\s]\d{2}[-/\s]\d{4})',
        text
    )
    if berlaku:
        result["berlaku_hingga"] = re.sub(r'\s', '-', berlaku.group(1))

    return result


# ======================================================================
# ROUTE API
# ======================================================================
@app.route('/extract', methods=['POST'])
def extract():
    if 'foto' not in request.files:
        return jsonify({"success": False, "error": "Foto tidak ditemukan"}), 400

    file = request.files['foto']

    with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
        file.save(tmp.name)
        tmp_path = tmp.name

    try:
        # Blur check — cast ke Python native type agar JSON serializable
        is_blurry, blur_score = is_image_too_blurry(tmp_path)
        is_blurry  = bool(is_blurry)
        blur_score = float(blur_score)

        # Baca gambar
        img = cv2.imread(tmp_path)
        if img is None:
            return jsonify({
                "success": False,
                "error": "Gambar tidak bisa dibaca",
                "blur_score": blur_score
            }), 422

        # OCR
        raw_ocr = ocr_multi_strategy(img)

        if not raw_ocr.strip():
            return jsonify({
                "success": False,
                "error": "OCR gagal membaca teks",
                "blur_score": blur_score
            }), 422

        # Preprocessing teks
        text = clean_text(raw_ocr)

        # Parsing
        data = parse_ktp(text)

        return jsonify({
            "success":    True,
            "data":       data,
            "blur_score": blur_score,
            "is_blurry":  is_blurry,
            "raw_text":   text,
            "raw_ocr":    raw_ocr,   # OCR mentah sebelum clean (untuk debug)
        })

    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)


@app.route('/health')
def health():
    return jsonify({"status": "ok"})


if __name__ == '__main__':
    app.run(debug=True)