# Video Upload with Compression - Setup Guide

## Overview
The video upload feature has been implemented with automatic compression using FFmpeg. Videos are compressed to reduce file size while maintaining good quality.

## Features Implemented

### 1. Backend (PHP)
- **Files Created:**
  - `admin/upload_video.php` - Handles video uploads for admin panel
  - `doctorpanel/upload_video.php` - Handles video uploads for doctor panel

- **Compression Settings:**
  - Video codec: H.264 (libx264)
  - Max resolution: 1280x720 (scales down if larger, maintains aspect ratio)
  - Quality: CRF 28 (good balance between quality and file size)
  - Audio: AAC @ 128kbps
  - Web optimization: Fast start enabled (progressive playback)

- **Features:**
  - Validates file type (MP4, MPEG, MOV, AVI, WebM, FLV)
  - Max upload size: 100MB
  - Generates unique filenames with timestamps
  - Stores videos in: `uploads/doctors/{doctor_id}/videos/`
  - Returns compression statistics (original size, compressed size, ratio)
  - Graceful fallback if FFmpeg is not available (uploads without compression)

### 2. Frontend Updates
- **Files Updated:**
  - `admin/edit.php` - Added "Upload Video" button in Videos section
  - `doctorpanel/edit.php` - Added "Upload Video" button in Videos section

- **UI Features:**
  - Purple gradient upload button with icon
  - File size validation (100MB max)
  - Upload progress indicator (shows "Compressing..." during upload)
  - Compression statistics displayed in alert
  - Uploaded video URLs automatically added to the videos list

## FFmpeg Installation

### Windows (XAMPP)

#### Option 1: Download Pre-built Binary (Recommended)
1. Download FFmpeg from: https://www.gyan.dev/ffmpeg/builds/
   - Choose "ffmpeg-release-essentials.zip"
   
2. Extract the ZIP file to: `C:\ffmpeg\`

3. Add to System PATH:
   - Right-click "This PC" → Properties
   - Click "Advanced system settings"
   - Click "Environment Variables"
   - Under "System variables", find "Path" and click "Edit"
   - Click "New" and add: `C:\ffmpeg\bin`
   - Click OK on all windows
   
4. Verify installation:
   ```bash
   ffmpeg -version
   ```

#### Option 2: Using Chocolatey
If you have Chocolatey installed:
```bash
choco install ffmpeg
```

### Alternative: Update upload_video.php Path
If you can't add FFmpeg to PATH, edit both upload files:
- `admin/upload_video.php` (line 66)
- `doctorpanel/upload_video.php` (line 64)

Change:
```php
$ffmpegPath = 'ffmpeg';
```

To:
```php
$ffmpegPath = 'C:\\ffmpeg\\bin\\ffmpeg.exe'; // Use your actual path
```

## Testing the Feature

### 1. Without FFmpeg (Fallback Mode)
- Videos will upload successfully but won't be compressed
- Message: "Video uploaded successfully (compression skipped - FFmpeg not available)"

### 2. With FFmpeg (Full Compression)
- Videos will be compressed automatically
- Message: "Video uploaded and compressed successfully (reduced by X%)"
- Shows original and compressed file sizes

## Usage Instructions

### For Admins/Doctors:
1. Go to Edit Profile page
2. Scroll to "Videos" section
3. Click "Upload Video" button (purple)
4. Select a video file (MP4, MOV, AVI, etc. - max 100MB)
5. Wait for compression (may take 30 seconds to 2 minutes depending on video size)
6. Video URL will be automatically added to the list
7. Save the profile to persist changes

### Video Formats Supported:
- Input: MP4, MPEG, MOV, AVI, WebM, FLV
- Output: Always MP4 (H.264) for maximum compatibility

## Storage Structure
```
uploads/
  └── doctors/
      └── {doctor_id}/
          └── videos/
              ├── video1_timestamp_compressed.mp4
              ├── video2_timestamp_compressed.mp4
              └── ...
```

## Compression Details

### Example Results:
- 50MB video → ~12-15MB (70-75% reduction)
- 20MB video → ~5-8MB (60-75% reduction)
- Quality remains very good for web playback

### Settings Used:
- **CRF 28**: Constant Rate Factor (lower = better quality, 18-28 is good range)
- **Preset: medium**: Balance between compression speed and file size
- **Resolution limit**: 1280x720 max (HD ready)
- **Audio**: 128kbps AAC (good quality for speech/music)

## Troubleshooting

### 1. "Failed to create upload directory"
- Check folder permissions: `uploads/doctors/` should be writable
- On Windows: Right-click folder → Properties → Security → Edit → Add write permission

### 2. "FFmpeg not available" but it's installed
- Verify FFmpeg is in PATH: Run `ffmpeg -version` in terminal
- Restart Apache/XAMPP after adding to PATH
- Check PHP can execute commands: `exec()` must not be disabled in php.ini

### 3. "Upload failed" or timeout
- Increase PHP upload limits in php.ini:
  ```ini
  upload_max_filesize = 100M
  post_max_size = 100M
  max_execution_time = 300
  ```
- Restart Apache after changes

### 4. Videos not playing after upload
- Check file permissions
- Verify the URL path is correct (should start with `/uploads/doctors/...`)
- Check browser console for errors

## Performance Notes

### Compression Time (Approximate):
- 10MB video: ~15-30 seconds
- 50MB video: ~1-2 minutes
- 100MB video: ~2-4 minutes

### Server Requirements:
- PHP 7.4+ with `exec()` enabled
- FFmpeg installed (optional but recommended)
- At least 500MB free disk space for temporary files during compression
- Adequate PHP execution time limit (300+ seconds recommended)

## Security Notes

1. **File validation**: Only video mime types are accepted
2. **Unique filenames**: Prevents overwrites and conflicts
3. **Size limits**: 100MB maximum to prevent abuse
4. **Directory isolation**: Each doctor has separate folder
5. **Authentication**: Both upload endpoints require admin/doctor login

## Future Enhancements (Optional)

1. **Progress bar**: Show real-time upload/compression progress
2. **Background processing**: Use job queue for large videos
3. **Multiple quality options**: Let users choose compression level
4. **Thumbnail generation**: Auto-generate video thumbnail
5. **Direct video player**: Embed video player in profile view
6. **Batch upload**: Upload multiple videos at once
7. **Video duration limit**: Restrict to X minutes max

## Support

If you encounter issues:
1. Check PHP error logs: `xampp/php/logs/php_error_log`
2. Check Apache error logs: `xampp/apache/logs/error.log`
3. Enable error display in upload_video.php for debugging
4. Test FFmpeg manually: `ffmpeg -i input.mp4 output.mp4`
