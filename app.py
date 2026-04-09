import os
import sys
import re
import cv2
import numpy as np
from flask import Flask, request, jsonify
import tempfile

os.environ["FLAGS_use_mkldnn"] = "0"
os.environ["PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK"] = "True"

# ==============================================================================
# PADDLEOCR INIT — pakai model nano/server agar ringan
# ==============================================================================

from paddleocr import PaddleOCR

# use_angle_cls=True  → deteksi teks rotasi/miring
# lang='id'           → Bahasa Indonesia (latin)
# use_gpu=False       → CPU only, ringan
# show_log=False      → tidak spam log ke terminal
# det_model_dir / rec_model_dir bisa diisi path lokal jika sudah download manual
ocr_engine = PaddleOCR(
    use_textline_orientation=True,
    device="cpu",
    text_det_thresh=0.3,
    text_det_box_thresh=0.5,
    text_recognition_batch_size=6,
    lang="id",
)

app = Flask(__name__)


# ==============================================================================
# BLUR CHECK — Laplacian variance
# ==============================================================================

def is_image_too_blurry(image_path, threshold=80):
    """
    Threshold diturunkan ke 80 (dari 120) karena PaddleOCR sudah
    lebih tahan terhadap buram; kita tetap informasikan skor ke caller.
    """
    img = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
    if img is None:
        return True, 0.0
    score = cv2.Laplacian(img, cv2.CV_64F).var()
    return score < threshold, round(score, 2)


# ==============================================================================
# PREPROCESSING — kunci keterbacaan gambar buram & kurang cahaya
# ==============================================================================

def preprocess_image(img: np.ndarray) -> np.ndarray:
    """
    Pipeline preprocessing bertahap:
    1. Resize ke lebar standar (agar teks tidak terlalu kecil untuk OCR)
    2. CLAHE  → pemerataan histogram adaptif (anti low-light)
    3. Denoise → buang noise sensor kamera
    4. Sharpen → pertajam tepi teks yang buram
    5. Threshold adaptif → binarisasi tahan pencahayaan tidak merata
    Output: BGR 3-channel (yang diharapkan PaddleOCR)
    """
    # 1. Resize — lebarkan ke 1200px jika terlalu kecil
    h, w = img.shape[:2]
    if w < 1200:
        scale = 1200 / w
        img = cv2.resize(img, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # 2. CLAHE (Contrast Limited Adaptive Histogram Equalization)
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    # 3. Denoise non-local means — efektif untuk gambar noise/buram
    gray = cv2.fastNlMeansDenoising(gray, h=10, templateWindowSize=7, searchWindowSize=21)

    # 4. Unsharp masking — pertajam teks
    blur = cv2.GaussianBlur(gray, (0, 0), 3)
    gray = cv2.addWeighted(gray, 1.8, blur, -0.8, 0)

    # 5. Adaptive threshold — lebih tahan shadow & cahaya tidak merata
    binary = cv2.adaptiveThreshold(
        gray, 255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        blockSize=31,
        C=10
    )

    # Kembalikan ke BGR karena PaddleOCR ekspek 3-channel
    return cv2.cvtColor(binary, cv2.COLOR_GRAY2BGR)


# ==============================================================================
# OCR DENGAN PADDLEOCR
# ==============================================================================

def run_paddle_ocr(img: np.ndarray) -> str:
    """
    Jalankan PaddleOCR pada:
    - Gambar asli (BGR)
    - Gambar preprocessed
    Lalu gabungkan & kembalikan teks terpanjang (paling lengkap).
    """
    results_pool = []

    def extract_text(predict_result) -> str:
        """Parse format output baru dari ocr_engine.predict()"""
        if not predict_result:
            return ""
        lines = []
        for page in predict_result:
            rec_texts = page.get("rec_texts", [])
            rec_scores = page.get("rec_scores", [])
            for text, score in zip(rec_texts, rec_scores):
                if text and score > 0.3:  # filter teks dengan confidence rendah
                    lines.append(text)
        return "\n".join(lines)

    # Coba gambar asli
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
            cv2.imwrite(tmp.name, img)
            raw_result = ocr_engine.predict(tmp.name)
            results_pool.append(extract_text(raw_result))
        os.unlink(tmp.name)
    except Exception as e:
        print(f"[OCR raw] error: {e}")

    # Coba gambar preprocessed
    try:
        processed = preprocess_image(img.copy())
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
            cv2.imwrite(tmp.name, processed)
            proc_result = ocr_engine.predict(tmp.name)
            results_pool.append(extract_text(proc_result))
        os.unlink(tmp.name)
    except Exception as e:
        print(f"[OCR processed] error: {e}")

    # Pilih hasil terpanjang
    best = max(results_pool, key=lambda x: len(x.strip()), default="")
    return best

# ==============================================================================
# NORMALISASI KARAKTER OCR NOISE
# ==============================================================================

# Peta karakter yang sering salah baca OCR
OCR_CHAR_MAP = {
    # Angka yang terbaca sebagai huruf
    '0': ['O', 'Q', 'D'],
    '1': ['I', 'L', '|'],
    '2': ['Z'],
    '5': ['S'],
    '6': ['G'],
    '7': ['Y', 'T'],
    '8': ['B'],
    '9': ['q', 'g'],
}

# Balik mapping: huruf → angka (untuk konteks tanggal/angka)
CHAR_TO_DIGIT = {}
for digit, chars in OCR_CHAR_MAP.items():
    for c in chars:
        CHAR_TO_DIGIT[c.upper()] = digit
        CHAR_TO_DIGIT[c.lower()] = digit

def fix_angka_ocr(token: str) -> str:
    """
    Perbaiki token yang seharusnya angka tapi terbaca sebagai campuran huruf.
    Contoh: '1C2Y' → '1027', 'I997' → '1997'
    Hanya dijalankan jika token mengandung campuran angka + huruf.
    """
    if not token:
        return token
    # Jika sudah pure angka, tidak perlu diproses
    if token.isdigit():
        return token
    # Jika tidak ada angka sama sekali, bukan token tanggal
    if not any(c.isdigit() for c in token):
        return token
    # Konversi karakter noise ke angka
    result = ''
    for c in token:
        if c.isdigit():
            result += c
        elif c.upper() in CHAR_TO_DIGIT:
            result += CHAR_TO_DIGIT[c.upper()]
        else:
            result += c  # biarkan karakter lain apa adanya
    return result

def fix_tanggal_ocr(text: str) -> str:
    """
    Perbaiki berbagai format tanggal yang rusak akibat OCR noise.
    Contoh:
        '14-11 1C2Y'  → '14-11-1997'
        '14-11-1C2Y'  → '14-11-1997'
        'I4-11-1997'  → '14-11-1997'
        '14/11/1997'  → '14-11-1997'
    """
    # Pola: DD-MM[-/spasi]YYYY (dengan kemungkinan noise)
    def replace_tgl(m):
        dd   = fix_angka_ocr(m.group(1))
        mm   = fix_angka_ocr(m.group(2))
        yyyy = fix_angka_ocr(m.group(3))
        # Validasi sederhana
        if len(dd) == 2 and len(mm) == 2 and len(yyyy) == 4:
            return f"{dd}-{mm}-{yyyy}"
        return m.group(0)  # kembalikan asli jika tidak valid

    # Tangkap DD-MM[-/ ]YYYY dengan karakter noise
    text = re.sub(
        r'\b([0-9A-Z]{2})[-/]([0-9A-Z]{2})[-/\s]([0-9A-Z]{4})\b',
        replace_tgl,
        text,
        flags=re.IGNORECASE
    )
    return text

def clean_text(text: str) -> str:
    text = text.upper()

    # Normalisasi label penting
    text = re.sub(r'KEL[/\s]?DESA', 'KEL DESA', text)
    text = text.replace("KELDESA", "KEL DESA")
    text = text.replace("TEMPAT/TGL LAHIR", "TEMPAT TGL LAHIR")
    text = text.replace("TEMPAT/TGL. LAHIR", "TEMPAT TGL LAHIR")
    text = text.replace("TEMPAT TGL. LAHIR", "TEMPAT TGL LAHIR")

    replacements = {
        "KELL ":              "KEL ",
        "RTIRW":              "RT/RW",
        "RT IRW":             "RT/RW",
        "BERLAKUHINGGA":      "BERLAKU HINGGA",
        "BERTAKU HINGGA":     "BERLAKU HINGGA",
        "TOMPATTGLAHIR":      "TEMPAT TGL LAHIR",
        "TGLAHIR":            "TGL LAHIR",
        "JONIS KALAMIN":      "JENIS KELAMIN",
        "STAHS PORKWINART":   "STATUS PERKAWINAN",
        "KEWERPANEGARAAN":    "KEWARGANEGARAAN",
    }
    for k, v in replacements.items():
        text = text.replace(k, v)

    # Perbaiki tanggal noise secara dinamis
    text = fix_tanggal_ocr(text)

    # Hapus karakter aneh
    text = re.sub(r'[^\w\s:/\-.,]', ' ', text)

    # Rapikan spasi
    text = re.sub(r'\s+', ' ', text)

    return text.strip()

# ==============================================================================
# PARSER KTP
# ==============================================================================

def parse_ktp(text: str) -> dict:
    result = {
        "nik": None, "nama": None, "tempat_lahir": None,
        "tanggal_lahir": None, "jenis_kelamin": None, "gol_darah": None,
        "alamat": None, "rt_rw": None, "kel_desa": None,
        "kecamatan": None, "agama": None, "status_perkawinan": None,
        "pekerjaan": None, "kewarganegaraan": None, "berlaku_hingga": None,
    }

    # NIK — 16 digit
    nik = re.search(r'\b(\d{16})\b', text)
    if nik:
        result["nik"] = nik.group(1)

    # NAMA — lebih toleran, tangkap apapun setelah NAMA :
    nama = re.search(r'NAM[A4]\s*[-:.]?\s*([A-Z][A-Z\s]{2,40}?)(?=\s+(?:TEMPAT|TGL|LAHIR|JENIS|NIK|\d))', text)
    if nama:
        result["nama"] = nama.group(1).strip()

    # TEMPAT/TGL LAHIR — toleran typo label
    ttl = re.search(
        r'(?:TEMPAT|TOMPAT|TMPAT)[^:]*?(?:TGL|TGI)[^:]*?(?:LAHIR|LAHIP)\s*[-:.]?\s*'
        r'([A-Z][A-Z\s]{1,20}?)[,\s]+(\d{2}[-/]\d{2}[-/]\d{4})',
        text
    )
    if ttl:
        result["tempat_lahir"] = ttl.group(1).strip()
        result["tanggal_lahir"] = ttl.group(2).replace("/", "-")
    else:
        # Fallback: cari tanggal DD-MM-YYYY saja
        tgl = re.search(r'\b(\d{2}[-/]\d{2}[-/]\d{4})\b', text)
        if tgl:
            result["tanggal_lahir"] = tgl.group(1).replace("/", "-")
        # Fallback tempat lahir: kata sebelum tanggal
        if result["tanggal_lahir"]:
            tpt = re.search(
                r'(?:LAHIR|LAHIP)\s*[-:.]?\s*([A-Z][A-Z\s]{1,20}?)[,\s]+' + re.escape(result["tanggal_lahir"]),
                text
            )
            if tpt:
                result["tempat_lahir"] = tpt.group(1).strip()

    # JENIS KELAMIN
    if re.search(r'PEREMPUAN', text):
        result["jenis_kelamin"] = "PEREMPUAN"
    elif re.search(r'LAKI', text):
        result["jenis_kelamin"] = "LAKI-LAKI"

    # GOL DARAH — toleran OCR noise (B bisa terbaca sebagai 8 atau 0)
    gol = re.search(r'(?:GOL|GA|GCL)[.\s]*(?:DARAH|DANNH|DARAL)[.\s]*[-:.]?\s*([ABO]{1,2}[+-]?|\d)', text)
    if gol:
        val = gol.group(1).replace("8", "B").replace("0", "O")
        result["gol_darah"] = val
    else:
        gol2 = re.search(r'\b(AB|A|B|O)\b', text)
        if gol2:
            result["gol_darah"] = gol2.group(1)

    # ALAMAT — toleran typo label
    alamat = re.search(
        r'(?:ALAMAT|ALAMAL|ALAMT)\s*[-:.]?\s*([A-Z0-9][A-Z0-9\s./\-]{3,60}?)\s*(?=RT|RW)',
        text
    )
    if alamat:
        val = re.sub(r'[\s./-]+$', '', alamat.group(1))
        result["alamat"] = re.sub(r'\s+', ' ', val).strip()

    # RT/RW
    rt = re.search(r'(\d{3})\s*/\s*(\d{3})', text)
    if rt:
        result["rt_rw"] = f"{rt.group(1)}/{rt.group(2)}"

    # KEL/DESA
    kel = re.search(
        r'KEL\s*DESA\s*[-:.]?\s*([A-Z][A-Z\s]{2,30}?)(?=\s*[-_.]?\s*(?:KEC|KECAMATAN|AGAMA|STATUS|\n|$))',
        text
    )
    if kel:
        result["kel_desa"] = kel.group(1).strip()

    # KECAMATAN
    kec = re.search(
        r'KECAMATAN\s*[-:.]?\s*([A-Z][A-Z\s]{2,30}?)(?=\s*(?:AGAMA|STATUS|PEKERJAAN|\n|$))',
        text
    )
    if kec:
        result["kecamatan"] = kec.group(1).strip()

    # AGAMA
    for agama in ["ISLAM", "KRISTEN", "KATOLIK", "HINDU", "BUDDHA", "KONGHUCU"]:
        if agama in text:
            result["agama"] = agama
            break

    # STATUS PERKAWINAN — toleran typo
    if re.search(r'BELUM\s*KAWIN', text):
        result["status_perkawinan"] = "BELUM KAWIN"
    elif re.search(r'CERAI', text):
        result["status_perkawinan"] = "CERAI"
    elif re.search(r'KAW[I1]N|KAWNN', text):
        result["status_perkawinan"] = "KAWIN"

    # ==============================================================================
    # DAFTAR PEKERJAAN RESMI KTP
    # ==============================================================================

    DAFTAR_PEKERJAAN = [
        "BELUM TIDAK BEKERJA", "BELUM / TIDAK BEKERJA", "BELUM/TIDAK BEKERJA",
        "BURUH NELAYAN PERIKANAN", "BURUH NELAYAN / PERIKANAN",
        "MENGURUS RUMAH TANGGA",
        "BURUH PETERNAKAN",
        "PELAJAR MAHASISWA", "PELAJAR / MAHASISWA", "PELAJAR/MAHASISWA",
        "PEMBANTU RUMAH TANGGA",
        "PENSIUNAN",
        "TUKANG CUKUR",
        "PEGAWAI NEGERI SIPIL", "PNS",
        "TENTARA NASIONAL INDONESIA", "TNI",
        "KEPOLISIAN RI", "POLRI",
        "PERDAGANGAN",
        "PETANI PEKEBUN", "PETANI / PEKEBUN", "PETANI/PEKEBUN",
        "PETERNAK",
        "NELAYAN PERIKANAN", "NELAYAN / PERIKANAN", "NELAYAN/PERIKANAN",
        "INDUSTRI",
        "KONSTRUKSI",
        "TRANSPORTASI",
        "KARYAWAN SWASTA",
        "KARYAWAN BUMN",
        "KARYAWAN BUMD",
        "KARYAWAN HONORER",
        "BURUH HARIAN LEPAS",
        "BURUH TANI PERKEBUNAN", "BURUH TANI / PERKEBUNAN",
        "TUKANG LISTRIK",
        "TUKANG BATU",
        "TUKANG KAYU",
        "TUKANG SOL SEPATU",
        "TUKANG LAS PANDAI BESI", "TUKANG LAS / PANDAI BESI",
        "TUKANG JAHIT",
        "PENATA RAMBUT",
        "PENATA RIAS",
        "PENATA BUSANA",
        "MEKANIK",
        "TUKANG GIGI",
        "SENIMAN",
        "TABIB",
        "PARAJI",
        "PERANCANG BUSANA",
        "PENERJEMAH",
        "IMAM MASJID",
        "PENDETA",
        "PASTUR",
        "WARTAWAN",
        "USTADZ MUBALIGH", "USTADZ / MUBALIGH",
        "JURU MASAK",
        "PROMOTOR ACARA",
        "ANGGOTA DPR RI", "ANGGOTA DPR-RI",
        "ANGGOTA DPD",
        "ANGGOTA BPK",
        "PRESIDEN",
        "WAKIL PRESIDEN",
        "ANGGOTA MAHKAMAH KONSTITUSI",
        "ANGGOTA KABINET KEMENTERIAN", "ANGGOTA KABINET / KEMENTERIAN",
        "DUTA BESAR",
        "GUBERNUR",
        "WAKIL GUBERNUR",
        "BUPATI",
        "WAKIL BUPATI",
        "WALIKOTA",
        "WAKIL WALIKOTA",
        "ANGGOTA DPRD PROVINSI",
        "ANGGOTA DPRD KABUPATEN KOTA", "ANGGOTA DPRD KABUPATEN / KOTA",
        "DOSEN",
        "GURU",
        "PILOT",
        "PENGACARA",
        "NOTARIS",
        "ARSITEK",
        "AKUNTAN",
        "KONSULTAN",
        "DOKTER",
        "BIDAN",
        "PERAWAT",
        "APOTEKER",
        "PSIKIATER PSIKOLOG", "PSIKIATER / PSIKOLOG",
        "PENYIAR TELEVISI",
        "PENYIAR RADIO",
        "PELAUT",
        "PENELITI",
        "SOPIR",
        "PIALANG",
        "PARANORMAL",
        "PEDAGANG",
        "PERANGKAT DESA",
        "KEPALA DESA",
        "BIARAWATI",
        "WIRASWASTA",
        "ANGGOTA LEMBAGA TINGGI",
        "ARTIS",
        "ATLET",
        "CHEF",
        "MANAJER",
        "TENAGA TATA USAHA",
        "OPERATOR",
        "PEKERJA PENGOLAHAN KERAJINAN", "PEKERJA PENGOLAHAN / KERAJINAN",
        "TEKNISI",
        "ASISTEN AHLI",
        "LAINNYA",
    ]

    # Urutkan dari terpanjang ke terpendek agar yang lebih spesifik dicek duluan
    DAFTAR_PEKERJAAN = sorted(DAFTAR_PEKERJAAN, key=len, reverse=True)
  
    # PEKERJAAN — cocokkan dengan whitelist resmi, toleran OCR noise
    def match_pekerjaan(text: str) -> str | None:
        # Normalisasi: hapus / dan - agar lebih mudah dicocokkan
        text_norm = re.sub(r'[/\-]', ' ', text)
        text_norm = re.sub(r'\s+', ' ', text_norm)

        for pekerjaan in DAFTAR_PEKERJAAN:
            pekerjaan_norm = re.sub(r'[/\-]', ' ', pekerjaan)
            pekerjaan_norm = re.sub(r'\s+', ' ', pekerjaan_norm)

            # Cek exact match dulu
            if pekerjaan_norm in text_norm:
                return pekerjaan

            # Fuzzy: tiap kata harus ada di teks (toleran urutan noise)
            kata_kata = pekerjaan_norm.split()
            if len(kata_kata) >= 2:
                pattern = r'\s+'.join(re.escape(k) for k in kata_kata)
                if re.search(pattern, text_norm):
                    return pekerjaan

        return None

    # Di dalam parse_ktp:
    pekerjaan = match_pekerjaan(text)
    if pekerjaan:
        result["pekerjaan"] = pekerjaan
    else:
        # Fallback regex kalau tidak ada di whitelist
        kerja = re.search(
            r'PEKERJAAN\s*[-:.]?\s*((?:[A-Z]+\s*){1,4}?)(?=\s*(?:KEWARGANEGARAAN|KEWER|BERLAKU|WNI|\n|$))',
            text
        )
        if kerja:
            result["pekerjaan"] = kerja.group(1).strip()

    # KEWARGANEGARAAN
    if re.search(r'WN[I1LJ]', text):
        result["kewarganegaraan"] = "WNI"
    elif re.search(r'WNA', text):
        result["kewarganegaraan"] = "WNA"

    # BERLAKU HINGGA
    berlaku = re.search(
        r'(?:BERLAKU|BERTAKU|BERLAK)\s*(?:HINGGA|HINGA)\s*[-:._]?\s*(\d{2}[-/]\d{2}[-/]\d{4}|SEUMUR\s*HIDUP)',
        text
    )
    if berlaku:
        result["berlaku_hingga"] = berlaku.group(1)

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
        # Cek blur (informatif, tidak blokir)
        is_blurry, blur_score = is_image_too_blurry(tmp_path)

        img = cv2.imread(tmp_path)
        if img is None:
            return jsonify({"success": False, "error": "Gambar tidak dapat dibaca"}), 422

        # Jalankan PaddleOCR (asli + preprocessed)
        raw_ocr = run_paddle_ocr(img)

        if not raw_ocr.strip():
            return jsonify({
                "success": False,
                "error": "OCR gagal membaca teks dari gambar",
                "blur_score": float(blur_score),
                "is_blurry": bool(is_blurry),
            }), 422

        text = clean_text(raw_ocr)
        data = parse_ktp(text)

        return jsonify({
            "success": True,
            "data": data,
            "blur_score": float(blur_score),
            "is_blurry": bool(is_blurry),
            "raw_text": text,
        })

    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)


@app.route('/health')
def health():
    return jsonify({"status": "ok", "engine": "PaddleOCR"})


if __name__ == '__main__':
    app.run(debug=True)