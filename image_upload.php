<?php
session_start();
include("funcs.php");
sschk();

$user_id = $_SESSION["user_id"];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>好み分析 - 画像アップロード</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .upload-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .upload-item {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s;
        }
        .upload-item:hover {
            border:#f5f2ed;
        }
        .upload-item input[type="file"] {
            margin: 10px 0;
        }
        .preview img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-top: 10px;
        }
        .analyze-btn {
            background: #e4b7e4;;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 30px auto;
            display: block;
        }
        .analyze-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .progress {
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
            color: #007bff;
        }

        body {
            display: block !important;
            align-items: unset !important;
            justify-content: unset !important;
            height: auto !important;
            padding: 20px !important;
        }
        
        /* ロゴを通常の位置に */
        .logo {
            position: static !important;
            top: unset !important;
            margin-bottom: 30px;
        }
        
        /* コンテンツラッパー */
        .content-wrapper {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        /* 全体のコンテナを縦並びに */
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        /* タイトルと説明を中央寄せ */
        h1, p {
            text-align: center;
            width: 100%;
        }

        /* フォーム要素を中央寄せ */
        form {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        form > div:first-child {
            margin: 30px auto !important;
            width: 90% !important;
            max-width: 500px !important;
        }

    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="logo" style="font-weight: bold;">emoStock</div>
        
        <h1>あなたの「好き」を分析します</h1>
        <p>いいなと思う画像を10枚アップロードしてください（最低5枚必要）</p>
        
        <div class="progress" id="progress">選択済み: 0/10枚</div>
        
        <form action="image_upload_act.php" method="post" enctype="multipart/form-data" id="uploadForm">
            <!-- 一括選択エリア -->
            <div style="text-align: center; margin: 30px auto; padding: 40px; border: 3px dashed gray; border-radius: 15px; background: #f8f9ff; display: flex; flex-direction: column; align-items: center; width: 90%; max-width: 500px;">
                <h3 style="color:black; margin-bottom: 15px;">画像を一括選択</h3>
                <p style="margin-bottom: 20px; color: #666;">複数の画像を一度に選択できます（最大10枚、最低5枚）</p>
                <input type="file" name="images[]" id="multipleImages" accept="image/*" multiple style="display: none;">
                <label for="multipleImages" style="background: #e4b7a0; color:black; font-weight:bold; padding: 15px 30px; border-radius: 50px; cursor: pointer; font-size: 16px; display: inline-block; transition: all 0.3s;">
                    画像を選択する
                </label>
            </div>
            
            <!-- プレビューエリア -->
            <div id="previewContainer" style="display: none;">
                <h3 style="text-align: center; color: #333; margin-bottom: 20px;">📸 選択された画像</h3>
                <div class="upload-container" id="previewGrid">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>
            
            <button type="submit" class="analyze-btn" id="analyzeBtn" disabled>分析開始（最低5枚必要）</button>
        </form>

        <p style="text-align: center;">
            <a href="select.php">戻る</a>
        </p>
    </div>

    <script>
        let selectedFiles = [];
        
        // 複数ファイル選択の処理
        document.getElementById('multipleImages').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            const previewContainer = document.getElementById('previewContainer');
            const previewGrid = document.getElementById('previewGrid');
            const progress = document.getElementById('progress');
            const analyzeBtn = document.getElementById('analyzeBtn');
            
            // 10枚制限チェック
            if (files.length > 10) {
                alert('画像は最大10枚まで選択できます。');
                return;
            }
            
            selectedFiles = files;
            
            // プレビューエリアをクリア
            previewGrid.innerHTML = '';
            
            if (files.length > 0) {
                previewContainer.style.display = 'block';
                
                files.forEach((file, index) => {
                    // ファイルタイプチェック
                    if (!file.type.startsWith('image/')) {
                        alert(`ファイル「${file.name}」は画像ファイルではありません。`);
                        return;
                    }
                    
                    // ファイルサイズチェック（5MB）
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`ファイル「${file.name}」のサイズが大きすぎます（5MB以下）。`);
                        return;
                    }
                    
                    // プレビュー要素作成
                    const previewItem = document.createElement('div');
                    previewItem.className = 'upload-item';
                    previewItem.innerHTML = `
                        <div style="font-weight: bold; margin-bottom: 10px;">画像 ${index + 1}</div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">${file.name}</div>
                        <div class="preview-image" id="preview${index}"></div>
                        <button type="button" onclick="removeImage(${index})" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-top: 10px; cursor: pointer;">削除</button>
                    `;
                    previewGrid.appendChild(previewItem);
                    
                    // 画像プレビュー表示
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById(`preview${index}`).innerHTML = 
                            `<img src="${e.target.result}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">`;
                    }
                    reader.readAsDataURL(file);
                });
            } else {
                previewContainer.style.display = 'none';
            }
            
            updateUI();
        });
        
        // 画像削除機能
        function removeImage(index) {
            selectedFiles.splice(index, 1);
            
            // ファイル入力をリセット
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            document.getElementById('multipleImages').files = dt.files;
            
            // UIを再構築
            document.getElementById('multipleImages').dispatchEvent(new Event('change'));
        }
        
        // UI更新
        function updateUI() {
            const progress = document.getElementById('progress');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const count = selectedFiles.length;
            
            progress.textContent = `選択済み: ${count}/10枚`;
            
            if (count >= 5) {
                analyzeBtn.disabled = false;
                analyzeBtn.textContent = `分析開始（${count}枚の画像）`;
                analyzeBtn.style.background = '#007bff';
            } else {
                analyzeBtn.disabled = true;
                analyzeBtn.textContent = `分析開始（最低5枚必要 - 現在${count}枚）`;
                analyzeBtn.style.background = '#ccc';
            }
        }

        // フォーム送信時の確認
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            if (selectedFiles.length < 5) {
                e.preventDefault();
                alert('最低5枚の画像を選択してください。');
                return false;
            }
            
            if (selectedFiles.length > 10) {
                e.preventDefault();
                alert('画像は最大10枚まで選択できます。');
                return false;
            }
            
            // 分析中の表示
            document.getElementById('analyzeBtn').textContent = '分析中...しばらくお待ちください';
            document.getElementById('analyzeBtn').disabled = true;
        });
        
        // 初期化
        updateUI();
    </script>
</body>
</html>