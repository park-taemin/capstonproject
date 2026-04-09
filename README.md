# n8n + Ollama Local Chatbot

Docker로 구동하는 로컬 LLM 챗봇 n8n 워크플로우

## 실행 방법

```bash
docker compose up -d
docker exec -it ollama ollama pull llama3
```

## 접속
- n8n: http://localhost:5678
- ID: admin / PW: admin1234

## 테스트
```bash
curl -X POST http://localhost:5678/webhook/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "안녕하세요!", "model": "llama3"}'
```
