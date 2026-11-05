import { MsEdgeTTS, OUTPUT_FORMAT } from 'msedge-tts';
import fs from 'fs';
import path from 'path';

async function test() {
  const tts = new MsEdgeTTS({ enableLogger: true });
  await tts.setMetadata('vi-VN-HoaiMyNeural', OUTPUT_FORMAT.AUDIO_24KHZ_48KBITRATE_MONO_MP3);
  
  const text = 'Nhà văn bất ngờ thừa nhận dùng ChatGPT để viết sách và đoạt giải thưởng Goncourt 2026.';
  
  const targetDir = path.resolve('backend/tts/output');
  const result = await tts.toFile(targetDir, text);
  console.log('Success! Result:', result);
}

test().catch(err => {
  console.error('TTS Error:', err);
  process.exit(1);
});
