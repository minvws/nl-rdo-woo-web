
ALTER TABLE decision_document OWNER TO woo_dba;
ALTER TABLE inventory OWNER TO woo_dba;

GRANT SELECT,INSERT,UPDATE ON TABLE decision_document TO woopie;
GRANT SELECT,INSERT,UPDATE ON TABLE inventory TO woopie;
