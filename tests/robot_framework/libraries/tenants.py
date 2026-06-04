"""
Robot Framework variable file that loads per-tenant configuration from the
`.env.rf.{environment}.{tenant}` files and exposes them as a single dictionary.

Usage in tests/resources (after passing --variablefile variables/tenants.py):
    ${TENANT_CONFIGS}            # dict keyed by tenant name
    ${TENANT_CONFIGS}[minvws]    # config dict for minvws
    ${TENANT_CONFIGS}[minfin]    # config dict for minfin
"""
import os
from pathlib import Path

_RF_DIR = Path(__file__).parent.parent
_KNOWN_TENANTS = ["minvws", "minfin"]


def _load_env_file(path: Path) -> dict:
    """Parse a dotenv file into a plain dictionary, ignoring comments and blanks."""
    config: dict[str, str] = {}
    try:
        for line in path.read_text().splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, value = line.partition("=")
            config[key.strip()] = value.strip()
    except FileNotFoundError:
        pass
    return config


_env = os.environ.get("ENVIRONMENT", "local")

TENANT_CONFIGS: dict[str, dict[str, str]] = {
    tenant: _load_env_file(_RF_DIR / "envs" / f".env.rf.{_env}.{tenant}")
    for tenant in _KNOWN_TENANTS
}
