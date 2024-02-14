import cv2

class QR:

    def read_qr_img(self, filename):
        image = cv2.imread(filename)
        detector = cv2.QRCodeDetector()
        data = detector.detectAndDecode(image)
        return data[0]
