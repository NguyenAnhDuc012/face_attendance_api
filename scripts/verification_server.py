import json
import os
from flask import Flask, request, jsonify
import base64     # Dùng để giải mã ảnh
import numpy as np  # Dùng để đọc mảng ảnh
import cv2          # Dùng để đọc mảng ảnh
import sys          # Dùng để ghi log (print ... file=sys.stderr)

# Tắt các log cảnh báo thừa của TensorFlow
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 

try:
    from deepface import DeepFace
    # Import các hàm cho model 'Facenet'
    from deepface.modules.verification import find_euclidean_distance, l2_normalize 
except ImportError:
    print("Lỗi: Cần 'deepface', 'flask', 'numpy', 'opencv-python'")
    exit()

# Khởi tạo server Flask
app = Flask(__name__)

# Model và Ngưỡng (Threshold)
MODEL_NAME = 'Facenet'
# Ngưỡng chuẩn cho Facenet L2. 
# Giá trị này nhỏ hơn 1.1 được coi là "khớp".
DISTANCE_THRESHOLD = 1.1 

@app.route('/verify-face', methods=['POST'])
def verify_face():
    try:
        data = request.json
        
        # 1. Nhận dữ liệu (Base64 và Vector)
        live_image_base64 = data.get('live_image_base64') 
        known_embedding = data.get('known_embedding')

        if not live_image_base64 or not known_embedding:
            return jsonify({"status": "error", "message": "Thiếu 'live_image_base64' hoặc 'known_embedding'"}), 400

        # Ghi log (sẽ xuất hiện trong file ai_server_err.log của Supervisor)
        print("[VerifyFace] Đã nhận yêu cầu, bắt đầu giải mã base64...", file=sys.stderr)
        
        # 2. GIẢI MÃ (DECODE) BASE64 VỀ LẠI ẢNH
        try:
            img_bytes = base64.b64decode(live_image_base64)
            nparr = np.frombuffer(img_bytes, np.uint8)
            # Biến 'img_cv' là ảnh đã được giải mã
            img_cv = cv2.imdecode(nparr, cv2.IMREAD_COLOR) 
            
            if img_cv is None:
                raise ValueError("Không thể decode ảnh từ base64")
        except Exception as e:
            print(f"[VerifyFace] LỖI GIẢI MÃ: {str(e)}", file=sys.stderr)
            return jsonify({"status": "error", "message": f"Lỗi giải mã ảnh: {str(e)}"}), 400
        
        print("[VerifyFace] Đã giải mã, bắt đầu tạo embedding (represent)...", file=sys.stderr)
        
        # 3. Tạo embedding cho ảnh live
        live_embedding_obj = DeepFace.represent(
            img_path=img_cv, # <-- Dùng ảnh đã giải mã
            model_name=MODEL_NAME, 
            enforce_detection=True # Bắt buộc tìm thấy mặt
        )
        live_embedding = live_embedding_obj[0]['embedding']
        
        print("[VerifyFace] Đã tạo embedding, bắt đầu so sánh...", file=sys.stderr)

        # 4. So sánh khoảng cách (Logic Facenet)
        live_embedding_normalized = l2_normalize(live_embedding)
        known_embedding_normalized = l2_normalize(known_embedding)
        
        distance = find_euclidean_distance(
            live_embedding_normalized, 
            known_embedding_normalized
        )
        
        is_match = distance <= DISTANCE_THRESHOLD

        # Ghi log kết quả cuối cùng
        print(f"[VerifyFace] KẾT QUẢ: Distance={distance:.4f} | Threshold={DISTANCE_THRESHOLD} | Match={is_match}", file=sys.stderr)

        # 5. Trả về kết quả
        return jsonify({
            "status": "success", 
            "match": True,
            "distance": float(distance)
        })

    except Exception as e:
        # Lỗi (ví dụ: "Face could not be detected" - không tìm thấy mặt trong ảnh live)
        print(f"[VerifyFace] LỖI (Ví dụ: Không tìm thấy mặt): {str(e)}", file=sys.stderr)
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    print(f"--- Đang chạy Dịch vụ AI (Flask) trên http://127.0.0.1:5000 ---")
    print(f"--- Dùng model: {MODEL_NAME}, Ngưỡng: {DISTANCE_THRESHOLD} ---")
    app.run(host='127.0.0.1', port=5000, debug=False) # Tắt debug mode