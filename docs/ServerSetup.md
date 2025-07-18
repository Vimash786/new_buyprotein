# Server Setup for Automatic Payout Generation

## Cron Job Setup

To enable automatic payout generation, you need to set up a cron job that runs Laravel's scheduler.

### 1. Open crontab
```bash
crontab -e
```

### 2. Add Laravel scheduler entry
```bash
# Run Laravel scheduler every minute
* * * * * cd /path/to/buyprotein && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/buyprotein` with the actual path to your Laravel project.

### 3. Alternative: Direct Command
If you prefer to run the payout command directly:
```bash
# Run payout generation daily at 9:00 AM
0 9 * * * cd /path/to/buyprotein && php artisan payouts:generate >> /dev/null 2>&1
```

## Verification

### Check if scheduler is working
```bash
# Check Laravel scheduler status
php artisan schedule:list

# Test payout generation manually
php artisan payouts:generate --force
```

### Monitor logs
```bash
# View Laravel logs
tail -f storage/logs/laravel.log
```

## Important Notes

1. **PHP Path**: Make sure to use the full path to PHP if needed:
   ```bash
   * * * * * cd /path/to/buyprotein && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Permissions**: Ensure the web server user has permission to write to storage/logs

3. **Environment**: Make sure the cron job runs in the same environment as your application

4. **Testing**: Always test the cron job after setup to ensure it's working correctly

## Windows Setup (if using Windows server)

For Windows servers, use Task Scheduler instead of cron:

1. Open Task Scheduler
2. Create Basic Task
3. Set trigger to "Daily" at 9:00 AM
4. Set action to "Start a program"
5. Program: `php`
6. Arguments: `artisan payouts:generate`
7. Start in: `C:\path\to\buyprotein`
