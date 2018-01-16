-- Steps and total duration
SELECT `telegram_id`, COUNT(*) AS `steps`, TIMESTAMPDIFF(MINUTE, MIN(`reached_on`), MAX(`reached_on`)) FROM `moves` GROUP BY `telegram_id` HAVING `steps` > 2;

-- Count of steps required to complete
SELECT `tmp`.`steps`, COUNT(*) FROM (SELECT `telegram_id`, COUNT(*) AS `steps` FROM `moves` GROUP BY `telegram_id`) AS `tmp` GROUP BY `tmp`.`steps` ORDER BY `tmp`.`steps` ASC;
