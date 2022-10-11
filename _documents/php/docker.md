# Docker cheatsheet 
Image is basically a template for a container.

## Image

build image: 
```
docker build -t tempsens-app ./docker
```

build all images in current directory:
```
docker build .
```

delete all images:
```
docker image prune -a
```

list existing docker images:
```
docker image ls
```

run image:
```
docker run -d -p 8090:80 --name tempsens-app -v "$PWD":/var/www tempsens-app
```

## Containers

Run containers:
```
docker compose up -d
```

show running containers:
```
docker ps
```

composer install:
```
docker exec tempsens-app composer install
```

Run image based on docker-compose configuration file:
```
docker compose --project-directory ./docker up
```


stop running container:
```
docker stop tempsens-app
```