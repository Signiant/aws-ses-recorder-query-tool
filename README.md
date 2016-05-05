# aws-ses-recorder-query-tool
A small PHP application which can query events captured by the aws-ses-recorder

# Purpose
This tool is the GUI component to the [ses bounce recorder lambda functions](https://github.com/Signiant/aws-ses-recorder).  It allows reporting on bounce and delivery notifications from AWS' Simple Email Service (SES)

# Usage
Once you have configured the Lambda functions to record the notifications (see docs in that project), you can install and use the UI.  It's packaged into a docker container for simplicity of deployment and can be invoked with:

## On an EC2 instance with a role configured to allow access to DynamoDB
```
docker run -d -p 8080:80 signiant/aws-ses-recorder-query-tool
```
## On an machine outside EC2
```
docker run -d -p 8080:80
              -e "AWS_ACCESS_KEY_ID=XXXX" \
              -e "AWS_SECRET_ACCESS_KEY=XXXX" \
              signiant/aws-ses-recorder-query-tool
```

The app will then be available at ```http://{docker host}:8080```

# Configuration
By default, the app will look for 2 DynamoDB tables in the us-east-1 region named as:

* DEVOPS_SES_BOUNCES
* DEVOPS_SES_DELIVERIES

These should match those that you configured the Lambda functions to write to.  To over-ride these names and the region for the app in the docker container you would:

1) Create a small YAML file of the form

```
dynamodb:
   bouncetable: "DEVOPS_SES_BOUNCES"
   deliverytable: "DEVOPS_SES_DELIVERIES"
   region: "us-east-1"
```
Edit the table names and region accordingly

2) Overide the config file by mounting it into the docker container as such:
```
docker run -d -p 8080:80
              -v "/path/to/local/config.yaml:/var/www/html/config.yaml" \
              signiant/aws-ses-recorder-query-tool
```

# Using the tool
Usage should be pretty self-explanatory.  Wildcards for the recipient are supported by using a *.  Note that while you can specify just a * and retrieve everything, it may take a long time to retrieve the results and display is not paginated. 
