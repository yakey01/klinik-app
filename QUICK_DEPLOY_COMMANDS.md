# Quick Deploy Commands for Claude with MCP

Once you have MCP servers configured, you can use these natural language commands with Claude to deploy your Laravel application.

## Basic Deployment Commands

### Deploy Latest Changes
```
"Deploy my Laravel app to production"
"Push the latest changes to the live server"
"Update production with current code"
```

### Database Operations
```
"Run database migrations on production"
"Backup the production database"
"Check migration status on the server"
"Rollback the last migration"
```

### Cache Management
```
"Clear all Laravel caches on production"
"Clear and rebuild application cache"
"Clear route cache and re-cache routes"
"Optimize Laravel for production"
```

### Emergency Operations
```
"Put the site in maintenance mode"
"Take the site out of maintenance mode"
"Emergency rollback to previous version"
"Check if the production site is up"
```

## Advanced Commands

### GitHub Integration
```
"Create a new release and deploy to production"
"Show me the deployment workflow status"
"Trigger a manual deployment"
"Check the latest GitHub Actions run"
```

### Server Management
```
"Check Laravel error logs on production"
"Show disk usage on the server"
"Check PHP version on production"
"List running processes on the server"
```

### Composer & NPM
```
"Run composer update on production"
"Install new composer packages"
"Update NPM packages and rebuild assets"
"Check outdated packages"
```

### Queue Management
```
"Restart Laravel queues"
"Check queue worker status"
"View failed jobs"
"Retry failed queue jobs"
```

## Monitoring Commands

### Health Checks
```
"Check if the API is responding"
"Run a full health check on production"
"Test database connectivity"
"Check application performance"
```

### Logs & Debugging
```
"Show me today's error logs"
"Check the last 50 lines of Laravel log"
"Search logs for specific error"
"Enable debug mode temporarily"
```

## Workflow Commands

### Full Deployment
```
"Do a complete production deployment with all checks"
"Deploy with database backup and migrations"
"Deploy without running migrations"
"Deploy and notify team on Slack"
```

### Testing Before Deploy
```
"Run tests before deploying"
"Check if it's safe to deploy"
"Validate deployment readiness"
"Show deployment checklist"
```

## Example Conversations

### Simple Deploy
```
You: "Deploy the latest changes to production"
Claude: "I'll deploy your Laravel application to production. Let me:
1. Trigger the GitHub Actions workflow
2. Monitor the deployment progress
3. Verify the site is working after deployment"
```

### Deploy with Checks
```
You: "Check if it's safe to deploy, then deploy if everything looks good"
Claude: "I'll check deployment readiness first:
1. Checking test status... ✅
2. Checking for pending migrations... Found 2
3. Checking server health... ✅
4. Creating database backup... ✅
Now deploying with migrations..."
```

### Troubleshooting
```
You: "The site is down after deployment, help!"
Claude: "I'll help troubleshoot the issue:
1. Checking server response... 500 error
2. Examining error logs... Found database connection error
3. Testing database connectivity... Failed
4. Rolling back to previous version... ✅
The site is back online. The issue appears to be with database credentials."
```

## Tips for Best Results

1. **Be specific**: "Deploy to production and skip migrations" is better than just "deploy"

2. **Chain commands**: "Clear cache, run migrations, then optimize the app"

3. **Ask for status**: "Show me the deployment progress" while deploying

4. **Request confirmations**: "Deploy to production after showing me what will change"

5. **Use conditions**: "Deploy only if all tests pass"

## Safety Features

Claude will:
- Always create backups before dangerous operations
- Ask for confirmation on destructive actions
- Show you what will be executed before running
- Provide rollback options if something goes wrong
- Monitor the deployment and report issues

## Getting Started

1. Say: "Help me set up MCP for deployment"
2. Say: "Test my deployment setup"
3. Say: "Show me how to deploy my app"

Claude will guide you through the entire process!