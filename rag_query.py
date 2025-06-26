import json
import faiss
import numpy as np
from sentence_transformers import SentenceTransformer

# Load the model and data
model = SentenceTransformer('all-MiniLM-L6-v2')
index = faiss.read_index("rag_index.faiss")

with open("metadata.json", "r", encoding="utf-8") as f:
    metadata = json.load(f)

# FAISS search function
def search_books(query, k=3):
    embedding = model.encode([query]).astype("float32")
    distances, indices = index.search(embedding, k)
    
    results = []
    for i in indices[0]:
        results.append(metadata[i])
    return results

# Command-line interface
if __name__ == "__main__":
    while True:
        query = input("🔎 Ask about a book or topic: ")
        if query.strip() == "":
            break
        results = search_books(query)
        print("\n📚 Top results:")
        for book in results:
            print(f"\n📘 Title: {book['title']}")
            print(f"✍️ Author: {book['author']}")
            print(f"📝 Description: {book['description']}")
            print(f"🏷️ Category: {book['category']}")