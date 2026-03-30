"""Module providing a function printing python version."""

import copy
import uuid
import yaml
from DataDriver.AbstractReaderClass import AbstractReaderClass
from DataDriver.ReaderConfig import TestCaseData

PYTHON_GUID = "<PYTHON GUID>"

def deep_merge(base: dict, override: dict) -> dict:
    """Merge override into base (recursive). Lists are replaced."""
    result = copy.deepcopy(base)
    for k, v in (override or {}).items():
        if isinstance(v, dict) and isinstance(result.get(k), dict):
            result[k] = deep_merge(result[k], v)
        else:
            result[k] = v
    return result

def sync_external_ids(body: dict, files: dict) -> None:
    """
    If body.documents[i].externalId == "<PYTHON GUID>", generate a GUID and copy it to:
      - body.documents[i].externalId
      - files.documents[i].externalId   (if that row exists)

    Same for attachments.
    Does NOT touch anything else.
    """

    def sync_list(section_name: str):
        body_list = body.get(section_name) or []
        files_list = files.get(section_name) or []

        for i, item in enumerate(body_list):
            ext_id = item.get("externalId")

            if ext_id == PYTHON_GUID:
                new_id = str(uuid.uuid4())
                item["externalId"] = new_id

                # only sync if corresponding files row exists
                if i < len(files_list) and isinstance(files_list[i], dict):
                    files_list[i]["externalId"] = new_id

        # put back in case they were missing
        if section_name in files:
            files[section_name] = files_list

    sync_list("documents")
    sync_list("attachments")

class yaml_reader(AbstractReaderClass):
    """Custom YAML reader for Robot Framework DataDriver"""

    def get_data_from_source(self):
        test_data = []

        file_path = self.kwargs.get("file_path")
        if not file_path:
            raise ValueError("DataDriver did not pass a file_path param to the reader.")

        with open(file_path, encoding="utf-8") as f:
            data = yaml.safe_load(f) or {}

        # Baseline
        baseline = data.get("baseline", {})
        baseline_req = baseline.get("request")
        baseline_skip = baseline.get("skip", False)
        baseline_tags = baseline.get("tags", [])

        testcases = data.get("testcases", [])
        test_data = []

        for testcase in testcases:
            # Merge skip/tags from baseline
            skip = testcase.get("skip", baseline_skip)
            tags = testcase.get("tags", baseline_tags)

            if skip:
                continue

            title = testcase.get("title", "Unnamed test")
            requests = testcase.get("requests") or [baseline_req]


            if not isinstance(requests, list) or not requests:
                raise ValueError(f"testcase '{title}': 'requests' must be a non-empty list")

            resolved_steps = []

            for idx, step in enumerate(testcase.get("steps", []), 1):
                step_name = step.get("name", f"Step {idx}")

                if "keyword" in step:
                    kw = step["keyword"]
                    resolved_steps.append({
                        "type": "keyword",
                        "name": step_name,
                        "keyword": kw["name"],
                        "args": kw.get("args", []),
                    })
                    continue

                if "request" in step:
                    req = deep_merge(baseline_req, step["request"] or {})

                    body = req.get("body") or {}
                    files = req.get("files") or {}

                    sync_external_ids(body, files)

                    resolved_steps.append({
                        "type": "request",
                        "name": step_name,
                        "body": body,
                        "files": files,
                        "expected_response_status": req.get("expected_response_status"),
                        "expected_publication_status": req.get("expected_publication_status"),
                    })
                    continue

                raise ValueError(f"Testcase '{title}', step '{step_name}' must define request or keyword")

            args = {"${steps}": resolved_steps}
            test_data.append(TestCaseData(title, args, tags))

        return test_data
