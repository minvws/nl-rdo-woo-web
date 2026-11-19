"""Reads a downloaded inventory Excel file."""

import openpyxl


def read_inventory_rows(excel_path: str) -> tuple:
    """Return (headers, rows) where each row is a dict of header -> cell value as a string.

    Empty cells become an empty string, so they compare equal to empty values in a request body.
    """
    wb = openpyxl.load_workbook(excel_path, read_only=True)
    ws = wb.active
    values = list(ws.iter_rows(values_only=True))
    wb.close()

    headers = [str(cell) for cell in values[0]]
    rows = [
        {header: '' if cell is None else str(cell) for header, cell in zip(headers, row)}
        for row in values[1:]
        if any(cell is not None for cell in row)
    ]
    return headers, rows


def get_related_document_numbers(document: dict, documents: list) -> str:
    """Return the inventory value for the documents that `document` refers to, by their external IDs."""
    return ';'.join(
        f"{related['publicationContext']}-{related['documentId']}".lower()
        for related in documents
        if related['externalId'] in document['refersTo']
    )
