# Esegue ogni notte alle 00:30
30 0 * * * /usr/bin/php /path/to/project/yii therapeutic-plan/update-status >> /path/to/logs/cronjob.log 2>&1