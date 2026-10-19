FROM node:20-alpine

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["sh", "-c", "if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi && if [ ! -d node_modules/.bin ]; then npm install; fi && npm run dev -- --host 0.0.0.0 --port 5173"]
