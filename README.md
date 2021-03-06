## Running the Application
1.Create a free [Xero developer account](https://developer.xero.com/) and load it with their sample data/create a sample company.

2.[Create a new app](https://developer.xero.com/myapps/) by clicking '**or try OAuth2**' in the upper right corner.

3.If you don't have a development environment, follow the instruction in [Laravel Homestead](https://laravel.com/docs/6.x/homestead) to set up your local development environment.

4.Clone the project, after that, create your own .env file, then add these lines at the end 
of the file.
```
XERO_CLIENT_ID="YOUR-CLIENT-ID"
XERO_CLIENT_SECRET="YOUR-CLIENT-SECRET"
XERO_REDIRECT_URI="THE-URI-YOU-SET-TO-POINT-TO-YOUR-PROJECT'S-PUBLIC-FOLDER/xero/redirect"
```

5.Run ```composer install``` in the root folder of the project.

6.Try to open the web application using localhost or the virtual host you have set. You should see the default Laravel home
page.

7.Add /xero to the end of the URI, you should see the xero user login page.

8.Login with your account, then follow the echo message to find the saved files.
