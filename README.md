# timeseriesdb-api
RESTful APIs for timeseriesdb: https://github.com/mbannert/timeseriesdb

timeseriesdb is PostgreSQL based database and R package that maps R time series objects into a PostgreSQL database. 
However, particularly on the GET side you might want to access/export your data without using R. 
Here's a basic RESTful GET API that exports time series to CSV and xlsx. 

Currently I am creating a PHP and nginx based API, nodejs and python are in the planning, too.  
