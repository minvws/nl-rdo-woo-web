"""Randomizes document IDs in a production report Excel file and its accompanying documents."""

import atexit
import os
import random
import shutil
import tempfile
import zipfile

import openpyxl

_DOC_ID_COL = 0  # column A = "ID"
_ID_MIN = 100_000_000
_ID_MAX = 999_999_999


def _make_tmp_dir() -> str:
    """Create a temp dir that is removed when the process (Robot run) exits."""
    tmp_dir = tempfile.mkdtemp()
    atexit.register(shutil.rmtree, tmp_dir, ignore_errors=True)
    return tmp_dir


def randomize(excel_path: str, docs_path: str | None = None, existing_mapping: dict | None = None) -> tuple:
    """Return (new_excel_path, new_docs_path_or_None, {str(old_id): str(new_id)}).

    Copies the Excel to a temp dir and replaces every value in col A (doc ID) with a
    random 9-digit number. When docs_path is a ZIP each file whose stem matches an old ID
    is renamed to the new ID. When docs_path is a single file it is renamed likewise.
    Pass existing_mapping to reuse IDs from a previous call (e.g. for replacement reports).
    """
    id_map: dict[str, str] = dict(existing_mapping or {})
    used_ids = set(id_map.values())
    tmp_dir = _make_tmp_dir()

    wb = openpyxl.load_workbook(excel_path)
    ws = wb.active

    for row in ws.iter_rows(min_row=2):
        cell = row[_DOC_ID_COL]
        if cell.value is not None:
            key = str(cell.value)
            if key not in id_map:
                new_id = str(random.randint(_ID_MIN, _ID_MAX))
                while new_id in used_ids:
                    new_id = str(random.randint(_ID_MIN, _ID_MAX))
                id_map[key] = new_id
                used_ids.add(new_id)
            cell.value = int(id_map[key])

    new_excel = os.path.join(tmp_dir, os.path.basename(excel_path))
    wb.save(new_excel)

    if docs_path is None:
        return new_excel, None, id_map

    if zipfile.is_zipfile(docs_path):
        new_docs = _randomize_zip(docs_path, id_map, tmp_dir)
    else:
        new_docs = _randomize_single_file(docs_path, id_map, tmp_dir)

    return new_excel, new_docs, id_map


def _randomize_zip(zip_path: str, id_map: dict, tmp_dir: str) -> str:
    extract_dir = os.path.join(tmp_dir, 'extracted')
    os.makedirs(extract_dir, exist_ok=True)

    with zipfile.ZipFile(zip_path) as zf:
        for name in zf.namelist():
            if not name.startswith('__MACOSX'):
                zf.extract(name, extract_dir)

    for filename in list(os.listdir(extract_dir)):
        stem, ext = os.path.splitext(filename)
        if stem in id_map:
            os.rename(
                os.path.join(extract_dir, filename),
                os.path.join(extract_dir, id_map[stem] + ext),
            )

    new_zip = os.path.join(tmp_dir, os.path.basename(zip_path))
    with zipfile.ZipFile(new_zip, 'w', zipfile.ZIP_STORED) as zf:
        for filename in os.listdir(extract_dir):
            zf.write(os.path.join(extract_dir, filename), filename)

    return new_zip


def rename_document_file(file_path: str, id_map: dict) -> str:
    """Copy a single document file to a temp dir, renaming its stem via id_map."""
    return _randomize_single_file(file_path, id_map, _make_tmp_dir())


def _randomize_single_file(file_path: str, id_map: dict, tmp_dir: str) -> str:
    stem, ext = os.path.splitext(os.path.basename(file_path))
    new_name = id_map.get(stem, stem) + ext
    new_path = os.path.join(tmp_dir, new_name)
    shutil.copy2(file_path, new_path)
    return new_path
