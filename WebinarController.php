<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\IvsRealTime\IvsRealTimeClient;
use Aws\Exception\AwsException;
use Aws\Ivs\IvsClient;
use App\Models\Webinar;
use App\Models\Broadcastdetails;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

class WebinarController extends Controller
{
    public function subscribers(Request $request)
    {
        return view('broadcaster.subscriber');
    }

    public function host(Request $request)
    {
        return view('broadcaster.host');
    }

    public function createIvsStage(Request $request) {
        try {
            require_once base_path('vendor/aws/aws-sdk-php/src/IVSRealTime/IVSRealTimeClient.php');
            $awsKey = config('services.aws.key');
            $awsSecret = config('services.aws.secret');
            $awsRegion = config('services.aws.region');
            $client = new IvsRealTimeClient([
                'version' => 'latest',
                'region' => $awsRegion,
                'credentials' => [
                    'key' => $awsKey,
                    'secret' => $awsSecret ,
                ],
                
            ]);
            $stageName = $request->stage_name;
            $newStageName = str_replace(' ', '-', $stageName);

            $result = $client->createStage([
                'name' => $newStageName, 
            ]);

            $local_arn = Str::random(32);
            $webinar = Webinar::create([
                'title'         => $stageName,
                'convertTitle'  => $newStageName,
                'description'   => $request->description,
                'scheduled_at'  => $request->scheduled_at,
                'stage_arn'     => $result['stage']['arn'],
                'local_arn'     => Str::random(32),
            ]);
            return redirect('/admin/webinars')->with('success','Stage created successfully.');
          
        } catch (AwsException $e) {
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function updateIvsStage(Request $request, $id){
        $stage = Webinar::findOrFail($id);
        $stage->title = $request->stage_name;
        $stage->description = $request->description;
        $stage->scheduled_at = $request->scheduled_at;
        $stageId = $request->stage_name;
        $scheduledTime = $request->scheduled_at;
        $stage->update();
        return redirect()->route('stage.create', $id)->with('success', 'Stage Updated Successfully');
    }
    
    public function uploadIvsStream(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:webm,mp4|max:51200', // Max 50MB
        ]);
    
        if (!$request->hasFile('video')) {
            return response()->json(['error' => 'No video file received'], 400);
        }
        $file = $request->file('video');

        if (!$file->isValid()) {
            return response()->json(['error' => 'Invalid video file'], 400);
        }
    
        $originalFilename = $file->getClientOriginalName();
        $filePath = $file->getPathname();
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // AWS S3 Client
        require_once base_path('vendor/aws/aws-sdk-php/src/S3/S3Client.php');
        $awsKey = config('services.aws.key');
        $awsSecret = config('services.aws.secret');
        $awsRegion = config('services.aws.region');
        $bucket = config('services.aws.awsBucket');
        
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => [
                'key' => $awsKey,
                'secret' => $awsSecret ,
            ],
            // 'http' => [
            //     'verify' => false,
            // ],
        ]);

        $bucket = env('AWS_BUCKET');
        try {
            // Step 1: Initialize Multipart Upload
            $result = $s3->createMultipartUpload([
                'Bucket' => $bucket,
                'Key'    => $filename,
                // 'ACL'    => 'public-read', // or 'private'
                'ContentType' => $file->getMimeType(),
            ]);
            
            $uploadId = $result['UploadId'];
            $partSize = 5 * 1024 * 1024; // 5MB
            $parts = [];
            $handle = fopen($filePath, 'rb');
            $partNumber = 1;

            // Step 2: Upload Parts
            while (!feof($handle)) {
                $data = fread($handle, $partSize);
                
                $uploadResult = $s3->uploadPart([
                    'Bucket'     => $bucket,
                    'Key'        => $filename,
                    'UploadId'   => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body'       => $data,
                ]);

                $parts[] = [
                    'PartNumber' => $partNumber,
                    'ETag'       => $uploadResult['ETag'],
                ];

                $partNumber++;
            }

            fclose($handle);

            // Step 3: Complete the Multipart Upload
            $result = $s3->completeMultipartUpload([
                'Bucket'   => $bucket,
                'Key'      => $filename,
                'UploadId' => $uploadId,
                'MultipartUpload' => ['Parts' => $parts],
            ]);

            return response()->json([
                'message' => 'Upload successful',
                'file_url' => $result['Location'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
   

}