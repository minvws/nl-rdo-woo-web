-- Migration Version20241015091005
-- Generated on 2024-10-16 05:36:10 by bin/console woopie:sql:dump
--

ALTER TABLE raw_inventory RENAME TO production_report;
ALTER TABLE inventory_process_run RENAME TO production_report_process_run;


