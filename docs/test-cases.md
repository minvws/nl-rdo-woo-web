<!-- markdownlint-disable MD013 -->
# Test cases

## CI

Test aim: prevent situations where information is accessible when it shouldn't, or information is missing when it should be accessible.

The following test cases have been identified for automation with a high priority in the CI flow.

| #   | Category     | Description                                                                                                                                           | Expected Result | Explanation                                                | Automated |
| --- | ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- | --------------- | ---------------------------------------------------------- | --------- |
| 1   | Woo decision | Upload a production report with N public files and a zip with N-1 files                                                                               | Fail            | Too few files                                              | Yes       |
| 2   | Woo decision | Upload a production report with N public files and a zip with N+1 files                                                                               | Success         | Excess files are ignored                                   | Yes       |
| 3   | Woo decision | Upload a production report with N public files and a zip with N other files                                                                           | Success         | Incorrect files are ignored                                | Yes       |
| 4   | Woo decision | Upload a production report with N public files, M non-public files, and a zip with N + M files                                                        | Success         | Excess files are ignored                                   | Yes       |
| 5   | Woo decision | Upload a production report with N public files, M already public files, and a zip with N + M files                                                    | Fail            | Already public files should not be in the inventory        | Yes       |
| 6   | Woo decision | In a public dossier with N public and M non-public documents, replace the production report with one where 1 non-public document has been made public | Check           | New public document awaits upload                          | Yes       |
| 7   | Woo decision | In a public dossier with N public and M non-public documents, replace the production report with one where 1 public document has been made non-public | Check           | Public document is no longer public                        | Yes       |
| 8   | Woo decision | In a public dossier with N public files, retract one of the documents                                                                                 | Check           | A retracted document should no longer be downloadable      | Yes       |
| 9   | Woo decision | In a public dossier with N public files, replace the production report with one where 1 public document is suspended                                  | Check           | The suspended document should no longer be available       | Yes       |
| 10  | Woo decision | In a public dossier with N public files, retract all documents via the Danger Zone                                                                    | Check           | All documents should be retracted                          | Yes       |
| 11  | Woo decision | Create a publication that becomes public in the future                                                                                                | Check           | This should not yet be discoverable on public              | Yes       |
| 12  | Woo decision | In a public dossier with N public files, replace the production report with a copy where one document is replaced with a new document                 | Fail            | A document should not be replaceable with a new report     |           |
| 13  | Woo decision | Retract a document that has already been published                                                                                                    | Fail            | A published document should not be retractable             |           |
| 13  | Woo decision | Publish a dossier in preview mode, with a future publish date                                                                                         | Check           | Dossier should only be accessible with link, not in search |           |

## Test & Acceptance

Test aim: Verify a deployment

The following test cases have been identified for automatic exection on Test and Acceptance.

- Check homepage for showing recent publications
- Basic happy flow: search, open dossier etc
