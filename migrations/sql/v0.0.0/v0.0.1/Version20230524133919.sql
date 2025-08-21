-- Migration Version20230524133919
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE woo_request RENAME TO inquiry;
ALTER TABLE woo_request_document RENAME TO inquiry_document;


