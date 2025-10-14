#!/bin/bash

echo "🚨 Stopping containers..."
sudo docker compose down

read -p "🧹 Do you want to remove orphaned volumes (except dbdata)? [y/N]: " remove_vols

if [[ "$remove_vols" =~ ^[Yy]$ ]]; then
  echo "Removing orphaned volumes..."
  for vol in $(docker volume ls -q); do
    if [[ "$vol" != "magento-docker_dbdata" ]]; then
      echo "Deleting volume: $vol"
      sudo docker volume rm -f $vol
    fi
  done
else
  echo "Skipping volume removal."
fi

echo "📦 Rebuilding containers and starting..."
sudo docker compose up -d --build 

echo "✅ Done. Containers started."

echo "Files inside the web container:"
sudo docker exec -it web ls /var/www/html
