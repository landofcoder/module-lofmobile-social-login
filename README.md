# module-lofmobile-social-login
Magento 2 Social Login api and Graphql for mobile app

Support social login via API and graphql

1. REST API Endpoints
- POST ```/V1/lofmobile/social_login```
+ token (string): access_token for social login
+ type (string): facebook, google, sms, firebase_sms, apple

Return: customer authorized token

- POST ```/V1/lofmobile/appleLogin```
+ token (string): access_token from apple
+ firstName (string|null): custom firstname
+ lastName (string|null): custom lastname

Return: customer authorized token

2. Graphql Queries

Social login:

```
mutation {
    generateSocialCustomerToken (social_token: String!, type: SocialLoginType!) {
        token
    }
}
```

Apple login:

```
mutation {
    generateAppleCustomerToken (apple_token: String!, first_name: String, last_name: String) {
        token
    }
}
