-- Migration Version20230513113425
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0);
ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0);
COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN "user".updated_at IS '(DC2Type:datetime_immutable)';


