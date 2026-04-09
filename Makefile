.PHONY: build push deploy scale logs

IMAGE_NAME=uco-tracer
WORKER_IMAGE=uco-tracer-worker
TAG?=latest
REGISTRY?=your-registry.com

build:
	docker build -t $(IMAGE_NAME):$(TAG) .
	docker build -f Dockerfile.worker -t $(WORKER_IMAGE):$(TAG) .

push:
	docker tag $(IMAGE_NAME):$(TAG) $(REGISTRY)/$(IMAGE_NAME):$(TAG)
	docker tag $(WORKER_IMAGE):$(TAG) $(REGISTRY)/$(WORKER_IMAGE):$(TAG)
	docker push $(REGISTRY)/$(IMAGE_NAME):$(TAG)
	docker push $(REGISTRY)/$(WORKER_IMAGE):$(TAG)

deploy:
	docker stack deploy -c docker-compose.prod.yml mudik

scale-app:
	docker service scale mudik_app=5

scale-worker:
	docker service scale mudik_worker=3

logs-app:
	docker service logs -f mudik_app

logs-worker:
	docker service logs -f mudik_worker

update:
	docker service update --image $(REGISTRY)/$(IMAGE_NAME):$(TAG) mudik_app
	docker service update --image $(REGISTRY)/$(WORKER_IMAGE):$(TAG) mudik_worker

rollback:
	docker service rollback mudik_app
	docker service rollback mudik_worker

clean:
	docker stack rm mudik

prod-up:
	docker compose -f docker-compose.prod.yml up -d --build

prod-down:
	docker compose -f docker-compose.prod.yml down
