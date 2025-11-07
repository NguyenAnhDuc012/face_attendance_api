import json
import os
from flask import Flask, request, jsonify

os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 

try:
    from deepface import DeepFace
    # ===== THAY ĐỔI IMPORT =====
    # Import hàm tính khoảng cách Euclidean L2
    from deepface.modules.verification import find_euclidean_distance, l2_normalize 
    # ============================
except ImportError:
    print("Lỗi: Cần 'deepface' và 'flask'. Chạy 'pip install deepface flask'")
    exit()

app = Flask(__name__)

# ===== THAY ĐỔI MODEL VÀ NGƯỠNG =====
MODEL_NAME = 'Facenet'
# Ngưỡng (threshold) chuẩn cho Facenet L2. 
# Giá trị này nhỏ hơn 1.1 được coi là "khớp".
DISTANCE_THRESHOLD = 1.1 
# ===================================

@app.route('/verify-face', methods=['POST'])
def verify_face():
    try:
        data = request.json
        live_image_path = data.get('live_image_path')
        known_embedding = data.get('known_embedding')

        if not live_image_path or not known_embedding:
            return jsonify({"status": "error", "message": "Thiếu 'live_image_path' hoặc 'known_embedding'"}), 400

        # 1. Tạo embedding cho ảnh live
        live_embedding_obj = DeepFace.represent(
            img_path=live_image_path, 
            model_name=MODEL_NAME, 
            enforce_detection=True
        )
        live_embedding = live_embedding_obj[0]['embedding']

        # ===== 2. THAY ĐỔI LOGIC TÍNH KHOẢNG CÁCH =====
        # 'Facenet' yêu cầu chuẩn hóa L2 trước khi tính khoảng cách
        live_embedding_normalized = l2_normalize(live_embedding)
        known_embedding_normalized = l2_normalize(known_embedding)
        
        distance = find_euclidean_distance(
            live_embedding_normalized, 
            known_embedding_normalized
        )
        # ============================================

        # 3. So sánh với ngưỡng
        is_match = distance <= DISTANCE_THRESHOLD

        return jsonify({
            "status": "success", 
            "match": True, 
            "distance": float(distance)
        })

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    print(f"--- Đang chạy Dịch vụ AI (Flask) trên http://127.0.0.1:5000 ---")
    print(f"--- Dùng model: {MODEL_NAME}, Ngưỡng: {DISTANCE_THRESHOLD} ---") # <-- Model đã đổi
    app.run(host='127.0.0.1', port=5000)