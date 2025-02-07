"""Module providing a function to read QR codes."""

import sys
from pyzbar.pyzbar import decode
from PIL import Image

class QR:
    """Class representing a QR code reader"""

    def read_qr_img(self, filename):
        """Function reading a QR image and returning the content."""

        image = Image.open(filename)
        if image is None:
            raise Exception("Unable to load image.")
        decoded_objects = decode(image)
        if decoded_objects:
            return decoded_objects[0].data.decode("utf-8")
        else:
            raise Exception("No QR code detected in the image.")

# Command-line execution support
if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python qr.py <image_path>")
    else:
        library = QR()
        result = library.read_qr_img(sys.argv[1])
        print(f"QR Code Data: {result}")
