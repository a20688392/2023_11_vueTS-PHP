# 純 PHP 安裝初始化

### 安裝
```sh
composer install
```

### 複製資料庫設定檔
```sh
cd src/Config
cp Database.php.example Database.php
# 在修改成自己的資料庫資訊
```


### 設置檢查規範
```sh
composer dev
```

### 檢查但不修正
```sh
composer lint
```

### 檢查並修正
```sh
composer lint:save
```

### 檢查並給出報告來自哪一個commit問題
```sh
composer lint:report
```
