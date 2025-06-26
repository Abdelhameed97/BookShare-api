from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import json, faiss, numpy as np
from sentence_transformers import SentenceTransformer

# ——— تحميل النموذج والبيانات ———
model = SentenceTransformer('all-MiniLM-L6-v2')
index = faiss.read_index("rag_index.faiss")
with open("metadata.json", "r", encoding="utf-8") as f:
    metadata = json.load(f)

# ——— إعداد FastAPI ———
app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class Query(BaseModel):
    question: str

@app.post("/query")
def query_docs(query: Query):
    q = query.question.strip().lower()
    if not q or len(q) < 2:
        return {"results": [], "message": "❓ لم أفهم سؤالك، من فضلك وضّح أكثر."}

    # — 1️⃣ البحث الدقيق —
    exact = []
    for book in metadata:
        searchable = " ".join([
            book.get("title",""),
            book.get("author",""),
            book.get("category",""),
            book.get("description",""),
            " ".join(book.get("tags",[])),
            book.get("educational_level","")
        ]).lower()
        if q in searchable:
            exact.append(book)
    if exact:
        return {"results": exact}

    # — 2️⃣ البحث الدلالي + فلترة نصّية + عتبة مسافة —
    emb = model.encode([query.question])
    D, I = index.search(np.array(emb).astype("float32"), 10)
    semantic = []
    seen = set()
    keywords = set(q.split())

    for dist, idx in zip(D[0], I[0]):
        if idx >= len(metadata) or dist > 0.8:
            continue
        book = metadata[idx]
        if book["title"] in seen:
            continue
        # تحقق أنّ عنوان الكتاب أو وصفه أو تصنيفه يحتوي إحدى كلمات الاستعلام
        text = " ".join([
            book.get("title",""),
            book.get("description",""),
            book.get("category",""),
            book.get("author",""),
            " ".join(book.get("tags",[])),
            book.get("educational_level","")
        ]).lower()
        if any(word in text for word in keywords):
            semantic.append(book)
            seen.add(book["title"])

    if semantic:
        return {"results": semantic}

    # — 3️⃣ لا توجد نتائج —
    return {"results": [], "message": "📕 لا توجد كتب متاحة لهذا الموضوع."}

@app.get("/api/books")
def get_books():
    return metadata

@app.get("/api/category")
def get_categories():
    cats = sorted({book["category"] for book in metadata if book.get("category")})
    return [{"id": i+1, "name": name} for i, name in enumerate(cats)]
