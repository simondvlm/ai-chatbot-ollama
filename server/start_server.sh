#!/bin/bash
gnome-terminal --tab -- bash -c "cd ~/Desktop/ollama-server && node server.js; exec bash"
gnome-terminal --tab -- bash -c "ngrok http 3001;"
clear
echo "server lancé avec succès !"