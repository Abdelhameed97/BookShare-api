import json
import faiss
import numpy as np
from sentence_transformers import SentenceTransformer

# ✅ تحميل النموذج المجاني (يشتغل محليًا)
model = SentenceTransformer('all-MiniLM-L6-v2')

# تحميل البيانات
with open("rag.json", "r", encoding="utf-8") as f:
    books = json.load(f)

# تجهيز البيانات
texts = []
metadata = []
vectors = []

for book in books:
    full_text = f"{book['title']}\n{book['author']}\n{book['description']}\n{book['category']}\n{' '.join(book['tags'])}"
    embedding = model.encode(full_text)
    texts.append(full_text)
    metadata.append(book)
    vectors.append(embedding)

# تحويل إلى numpy array
vector_data = np.array(vectors).astype("float32")

# بناء FAISS index
index = faiss.IndexFlatL2(len(vector_data[0]))
index.add(vector_data)

# حفظ index و metadata
faiss.write_index(index, "rag_index.faiss")
with open("metadata.json", "w", encoding="utf-8") as f:
    json.dump(metadata, f, ensure_ascii=False, indent=2)

print("✅ FAISS index built using sentence-transformers and saved.")
