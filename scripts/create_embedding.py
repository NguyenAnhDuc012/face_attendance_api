import sys
import json
import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 

try:
    from deepface import DeepFace
except ImportError:
    print(json.dumps({"status": "error", "message": "Thư viện deepface chưa được cài đặt. Chạy 'pip install deepface'."}))
    sys.exit(1)

try:
    image_path = sys.argv[1]

    # ===== THAY ĐỔI MODEL Ở ĐÂY =====
    embedding_objs = DeepFace.represent(
        img_path=image_path, 
        model_name='Facenet', # <-- Đổi từ 'VGG-Face'
        enforce_detection=True
    )
    # ================================
    
    vector = embedding_objs[0]['embedding']
    
    print(json.dumps({"status": "success", "embedding": vector}))

except Exception as e:
    print(json.dumps({"status": "error", "message": str(e)}))
    sys.exit(1)