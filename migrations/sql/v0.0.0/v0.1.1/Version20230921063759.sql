-- Migration Version20230921063759
-- Generated on 2023-09-21 13:46:54 by bin/console woopie:sql:dump
--

CREATE TABLE worker_stats (id UUID NOT NULL, section VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, duration INT NOT NULL, hostname VARCHAR(255) NOT NULL, PRIMARY KEY(id));
COMMENT ON COLUMN worker_stats.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN worker_stats.created_at IS '(DC2Type:datetime_immutable)';


