#!/usr/bin/env python3
"""
Extract 128-dim face descriptor from image.

Usage: python3 face_extract.py <image_path>

Output: JSON with descriptor array or null if no face detected.
Dependencies: face_recognition (pip install face_recognition)
"""
import json
import sys
import os

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"descriptor": None, "error": "No image path provided"}))
        sys.exit(1)

    image_path = sys.argv[1]

    if not os.path.isfile(image_path):
        print(json.dumps({"descriptor": None, "error": "File not found"}))
        sys.exit(1)

    try:
        import face_recognition
    except ImportError:
        print(json.dumps({
            "descriptor": None,
            "error": "face_recognition not installed. Run: pip install face_recognition"
        }))
        sys.exit(1)

    try:
        image = face_recognition.load_image_file(image_path)
        encodings = face_recognition.face_encodings(image)

        if not encodings:
            print(json.dumps({"descriptor": None, "error": "No face detected"}))
            sys.exit(0)

        descriptor = [float(v) for v in encodings[0]]
        print(json.dumps({"descriptor": descriptor}))
    except Exception as e:
        print(json.dumps({"descriptor": None, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
