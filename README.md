#Notification for Timers in H&H ###
This Telegram bot will notify you of current progress and finished timers created in ["Haven & Hearth" MMO.](http://www.havenandhearth.com/portal/) 
###Using the bot###

1. Add @BrodgarBot to Telegram's contacts    
2. Download modified version of H&H Custom client from Ender [here](modified hafen/ender)    
3. Follow the instructions  

###Running bot on own server###
Interested in running bot on own server?

1. Place php scripts on webserver. *Note: must be provided through HTTPS connection, as  Telegram API requires*
2. Set up simple mySQL database with [this](src/hnh_2016-08-29_15-56-24.sql)
3. Set up a Telegram bot following [this ](https://core.telegram.org/bots#create-a-new-bot)guide
4. Modify all scripts and paste in your credentials in the first lines where needed.
5. Now link created bot with scripts by setting up a webhook:  
 `https:// api.telegram. org/bot[Your Auth Token]/setWebhook?url=[URL to telegramBot.php]`
6. Set up a cronjob for *checkJob.php*. Call it every minute for as precise as possible notifications

![Alt text](https://camo.githubusercontent.com/b41c9ba49ef06d8c730f829164281a246cf0dfc1/687474703a2f2f692e696d6775722e636f6d2f694e7538666e392e706e673f31)