# 04 — API Sözleşmesi (v1, Sanctum)

Base: `https://optik.SITE/api` — `Accept: application/json`, mobil `Authorization: Bearer <token>`.

## Auth

- `POST /api/login` `{email,password,device_name}` → `{token, user:{id,name,email}}` (401 hatalı girişte)
- `POST /api/logout` (auth) → `{ok:true}` (token silinir)
- `GET /api/me` (auth) → `{id,name,email}`

## Sınav + form-spec (auth, login şart)

- `GET /api/exams` → `{data:[{id,title,course,question_count,option_count,booklets,status}]}` (sadece published + kendi sınavları)
- `GET /api/exams/{id}` →
```json
{
  "id": 7, "title": "Mat Deneme 1", "question_count": 40, "option_count": 5,
  "booklets": ["A","B"], "form_version": 3,
  "form_spec": { "page": "A4", "anchors": "corner-squares", "...": "tam spec için packages/form-spec/v1.json" }
}
```
Mobil bu spec'i cache'ler, QR'daki version ile eşleştirir.

## Tarama kaydet (auth)

- `POST /api/scans` multipart veya JSON:
```json
{
  "exam_id": 7, "booklet": "A", "student_no": "20240137",
  "answers": {"1":"B","2":"E","3":null,"4":["A","C"]},
  "confidence": 92, "device_id": "pixel-8-abc",
  "qr_payload": "OPTIK1:7:3:A:9f2c"
}
```
+ `paper_image` (jpg, max 6MB). Yanıt:
```json
{"id": 51, "score": 82.5, "max_score": 100, "status": "ok", "review_items": [4]}
```
- `GET /api/exams/{id}/scans?status=review` → öğretmen review kuyruğu (web de aynı veriyi Blade'den gösterir).

## Hata formatı

`{message, errors:{field:[...]}}` + HTTP kod (422 validasyon, 401 auth, 409 duplicate, 410 eski form_version).
