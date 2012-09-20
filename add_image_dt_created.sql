ALTER TABLE `tx__media_images`
  ADD COLUMN `dt_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `filename`
