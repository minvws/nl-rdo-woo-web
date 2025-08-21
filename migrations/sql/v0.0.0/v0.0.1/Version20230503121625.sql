-- Migration Version20230503121625
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE worker_stats (id UUID NOT NULL, section VARCHAR(100) NOT NULL, count INT NOT NULL, duration BIGINT NOT NULL, PRIMARY KEY(id));
ALTER TABLE worker_stats OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE worker_stats TO woopie;

COMMENT ON COLUMN worker_stats.id IS '(DC2Type:uuid)';


