<?php

namespace plugin\owladmin\app\trait;

use support\Response;
use Illuminate\Support\Str;
use plugin\owladmin\app\Admin;
use Illuminate\Support\Facades\Storage;

// todo 部分功能不支持webman
trait UploadTrait
{
    /**
     * 图片上传路径
     *
     * @return string
     */
    public function uploadImagePath(): string
    {
        return admin_url('upload_image');
    }

    public function uploadImage(): Response
    {
        return $this->upload('image');
    }

    /**
     * 文件上传路径
     *
     * @return string
     */
    public function uploadFilePath(): string
    {
        return admin_url('upload_file');
    }

    public function uploadFile(): Response
    {
        return $this->upload();
    }

    /**
     * 富文本编辑器上传路径
     *
     * @param bool $needPrefix
     *
     * @return string
     */
    public function uploadRichPath(bool $needPrefix = false): string
    {
        return admin_url('upload_rich', $needPrefix);
    }

    public function uploadRich(): Response
    {
        $fromWangEditor = false;
        $file           = request()->file('file');

        if (!$file) {
            $fromWangEditor = true;
            $file           = request()->file('wangeditor-uploaded-image');
            if (!$file) {
                $file = request()->file('wangeditor-uploaded-video');
            }
        }

        if (!$file) {
            return $this->response()->additional(['errno' => 1])->fail(admin_trans('admin.upload_file_error'));
        }

        $path = $file->store(Admin::config('admin.upload.directory.rich'), Admin::config('admin.upload.disk'));

        $link = Storage::disk(Admin::config('admin.upload.disk'))->url($path);

        if ($fromWangEditor) {
            return $this->response()->additional(['errno' => 0])->success(['url' => $link]);
        }

        return $this->response()->additional(compact('link'))->success(compact('link'));
    }

    protected function upload($type = 'file'): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return $this->response()->fail(admin_trans('admin.upload_file_error'));
        }

        $path = $file->store(Admin::config('admin.upload.directory.' . $type), Admin::config('admin.upload.disk'));

        return $this->response()->success(['value' => $path]);
    }

    public function chunkUploadStart(): Response
    {
        $uploadId = Str::uuid();

        cache()->put($uploadId, [], 600);

        appw('filesystem')->makeDirectory(base_path('public/chunk/' . $uploadId));

        return $this->response()->success(compact('uploadId'));
    }

    public function chunkUpload(): Response
    {
        $uploadId   = request()->input('uploadId');
        $partNumber = request()->input('partNumber');
        $file       = request()->file('file');

        $path = 'chunk/' . $uploadId;

        $file->storeAs($path, $partNumber, 'public');

        $eTag = md5(Storage::disk('public')->get($path . '/' . $partNumber));

        return $this->response()->success(compact('eTag'));
    }

    public function chunkUploadFinish(): Response
    {
        $fileName = request()->file('filename');
        $partList = request()->input('partList');
        $uploadId = request()->input('uploadId');
        $type     = request()->input('t');

        $ext      = pathinfo($fileName, PATHINFO_EXTENSION);
        $path     = $type . '/' . $uploadId . '.' . $ext;
        $fullPath = base_path('public/' . $path);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            appw('filesystem')->makeDirectory($dir);
        }

        for ($i = 0; $i < count($partList); $i++) {
            $partNumber = $partList[$i]['partNumber'];
            $eTag       = $partList[$i]['eTag'];

            $partPath = 'chunk/' . $uploadId . '/' . $partNumber;

            $partETag = md5(Storage::disk('public')->get($partPath));

            if ($eTag != $partETag) {
                return $this->response()->fail('分片上传失败');
            }

            file_put_contents($fullPath, Storage::disk('public')->get($partPath), FILE_APPEND);
        }

        clearstatcache();

        $value = admin_resource_full_path($path);

        appw('files')->deleteDirectory(base_path('public/chunk/' . $uploadId));

        return $this->response()->success(['value' => $value], '上传成功');
    }
}