
ALTER TABLE encrypted_audit_entry OWNER TO woo_dba;
GRANT INSERT ON TABLE encrypted_audit_entry TO woopie;

ALTER TABLE audit_entry OWNER TO woo_dba;
GRANT INSERT ON TABLE audit_entry TO woopie;

ALTER TABLE worker_stats OWNER TO woo_dba;
GRANT SELECT,INSERT ON TABLE worker_stats TO woopie;
