# 4d-sesam-data-source
4D database source for Sesam.io applications

### example system configuration

```json
{
  "_id": "kss-4d",
  "type": "system:microservice",
  "docker": {
    "environment": {
      "ALLOWED_TABLES": "[\"Accounts\",\"Contacts\"]",
      "DB_HOST": "$ENV(DB_HOST)",
      "DB_PASSWORD": "$SECRET(DB_PASSWORD)",
      "DB_PORT": "$ENV(DB_POSRT)",
      "DB_USER": "$ENV(DB_USER)"
    },
    "image": "ohuenno/4d-connector:latest",
    "port": 80
  }
}
```

### example pipe configuration
```json
{
  "_id": "kss-4d-people",
  "type": "pipe",
  "source": {
    "type": "conditional",
    "alternatives": {
      "dev": {
        "type": "embedded",
        "entities": []
      },
      "prod": {
        "type": "json",
        "system": "kss-4d",
        "url": "/Accounts"
      },
      "test": {
        "type": "json",
        "system": "kss-4d",
        "url": "/Accounts"
      }
    },
    "condition": "$ENV(env)"
  },
  "transform": {
    "type": "dtl",
    "rules": {
      "default": [
        ["copy", "*"],
        ["add", "rdf:type",
          ["ni", "foo", "bar"]
        ],
        ["make-ni", "email", "email"]
      ]
    }
  },
  "pump": {
    "cron_expression": "0 0 * * ?"
  }
}
```
