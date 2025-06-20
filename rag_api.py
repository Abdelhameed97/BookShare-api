from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import json, faiss, numpy as np
from sentence_transformers import SentenceTransformer

# تحميل النموذج والبيانات
model = SentenceTransformer('all-MiniLM-L6-v2')
index = faiss.read_index("rag_index.faiss")

with open("metadata.json", "r", encoding="utf-8") as f:
    metadata = json.load(f)

# إعداد FastAPI
app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ---------------------------
# 🔎 الشات الذكي
class Query(BaseModel):
    question: str

@app.post("/query")
def query_docs(query: Query):
    emb = model.encode([query.question])
    D, I = index.search(np.array(emb).astype("float32"), 5)
    seen = set()
    results = []
    for i in I[0]:
        book = metadata[i]
        if book["title"] not in seen:
            results.append(book)
            seen.add(book["title"])
    return {"results": results}

# 📚 عرض كل الكتب
@app.get("/api/books")
def get_books():
    return metadata

# 🏷️ عرض التصنيفات الفريدة
@app.get("/api/category")
def get_categories():
    categories = list(set(book["category"] for book in metadata if "category" in book))
    return [{"id": i+1, "name": name} for i, name in enumerate(categories)]
