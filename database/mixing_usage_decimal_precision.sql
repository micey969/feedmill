ALTER TABLE ingredient_usage
  MODIFY calculated_per_batch_used_kgs DECIMAL(10,3) NULL DEFAULT NULL,
  MODIFY calculated_total_used_kgs DECIMAL(10,3) NULL DEFAULT NULL,
  MODIFY total_variance_kgs DECIMAL(10,3) NULL DEFAULT NULL;
