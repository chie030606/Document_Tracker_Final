-- Migration: add updated_at column to requests table
-- Run this in phpMyAdmin or via the provided PHP runner.

ALTER TABLE `requests`
  ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`;
