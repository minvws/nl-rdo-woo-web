""" Module for disabling truststore info log messages such as the following:

        RPA.core.certificates - INFO - Truststore injection done, using system certificate store to validate HTTPS

    To disable this RPA message, import this module before any RPA library using 'Library  LogDisable.py'.
"""

import logging
from robot.api import SuiteVisitor

logging.getLogger("RPA.core.certificates").disabled = True

class LogDisable(SuiteVisitor): pass
