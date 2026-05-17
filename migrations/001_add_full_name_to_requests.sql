-- Migration: add full_name column to requests table
-- Run this in phpMyAdmin or via the provided PHP runner.

ALTER TABLE `requests`
  ADD COLUMN `full_name` VARCHAR(255) NULL AFTER `tracking_id`;
