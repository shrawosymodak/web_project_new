<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    //this method will show user registration page
    public function registration()
    {
        return view('front.account.registration');

    }
    public function processRegistration(Request $request){
        $validator=Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|min:5|same:confirm_password',
            'confirm_password'=>'required',

        ]);
        if($validator->passes()){

            $user=new User();
            $user->name=$request->name;
            $user->email=$request->email;
            $user->password=Hash::make($request->password);
            
            $user->save();
            session()->flash('success','You have registered successfully.');


            return response()->json([
                'status'=>true,
                'errors'=>[]

            ]);

        }else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()

            ]

            );
        }

    }
    public function login()
    {
        return view('front.account.login');

    }
    public function authenticate(Request $request){
        $validator=Validator::make($request->all(),[
            'email'=>'required|email',
            'password'=>'required'
        ]);
        if($validator->passes()){
            if(Auth::attempt(['email'=>$request->email,'password'=>$request->password])){
                return redirect()->route('account.profile');

            }else{
                return redirect()->route('account.login')->with('error','Either Email/Password is incorrect');
            }

        }else{
            return redirect()->route('account.login')->withErrors($validator)->withInput($request->only('email'));

        }
    }
    public function profile(){
        //echo Auth::user()->password;
       $id= Auth::user()->id;
       $user=User::where('id',$id)->first();
       //dd($user);
        return view('front.account.profile',[
            'user'=>$user
        ]);
    }

    public function updateProfile(Request $request){
        $id= Auth::user()->id;
        $validator=Validator::make($request->all(),[
            'name'=>'required|min:5|max:20',
            'email'=>'required|email|unique:users,email,'.$id.',id',
            'mobile'=>'required|digits:11',
        ]);


        if($validator->passes()){

            $user=User::find($id);
            $user->name=$request->name;
            $user->email=$request->email;
            $user->mobile=$request->mobile;
            $user->designation=$request->designation;
            
            $user->save();
            session()->flash('success','Profile updated successfully.');


            return response()->json([
                'status'=>true,
                'errors'=>[]

            ]);



        }
        else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()

            ]

            );
        }
        
       
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('account.login');
    }

    public function updateProfilePic(Request $request){
        //dd($request->all());
        $id= Auth::user()->id;
        $validator=Validator::make($request->all(),[
            'image'=>'required|image'
           
        ]);
        if($validator->passes()){
            $image=$request->image;
            $ext=$image->getClientOriginalExtension();
            $imageName=$id.'-'.time().'.'. $ext;
            $image->move(public_path('/profile_pic/'),$imageName);

            //create small thrumbnail
            $sourcePath=public_path('/profile_pic/'.$imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);

// crop the best fitting 5:3 (600x360) ratio and resize to 600x360 pixel
             $image->cover(150, 150);
             $image->toPng()->save(public_path('/profile_pic/thumb/'.$imageName));
             //deleting old pic
             File::delete(public_path('/profile_pic/thumb/'.Auth::user()->image));
             File::delete(public_path('/profile_pic/'.Auth::user()->image));


            User::where('id',$id)->update(['image'=>$imageName]);

            session()->flash('success','Profile picture updated successfully.');
            return response()->json([
                'status'=>true,
                'errors'=>[]

            ]

            );


           



        }
        else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()

            ]

            );
        }
        

       
    }
    public function createJob(){
       $categories= Category :: orderBy('name','ASC')->where('status',1)->get();
       $jobTypes= JobType :: orderBy('name','ASC')->where('status',1)->get();
        return view('front.account.job.create',[
          'categories'=> $categories,
          'jobTypes'=>$jobTypes
        ]);
    }
   
    public function saveJob(Request $request)
{
    $rules = [
        'title' => 'required|min:5|max:200',
        'category' => 'required',
        'jobType' => 'required',
        'vacancy' => 'required|integer',
        'location' => 'required|max:50',
        'description' => 'required',
        'company_name' => 'required|digits:11',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->passes()) {
        $job =new Job();
        $job->title= $request->title;
       // $categoryId = (int) $category->id;
        $job->category_id= $request->category;
        $job->job_type_id= $request->jobType;
        $job->user_id= Auth::user()->id;
        $job->vacancy= $request->vacancy;
        $job->salary= $request->salary;
        $job->location= $request->location;
        $job->description= $request->description;
        $job->benefits= $request->benefits;
        $job->responsibility= $request->responsibility;
        $job->qualifications= $request->qualifications;
        $job->experience= $request->experience;
        $job->keywords= $request->keywords;
        $job->company_name= $request->company_name;
        $job->company_location= $request->company_location;
        $job->company_website= $request->company_website;
        $job->save();
        
        session()->flash('success','Car added Successfully');

        
        // Process the job creation
        return response()->json([
            'status' => true,
            'errors' => []
        ]);
    } else {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(), // Convert errors to array for easier handling in JavaScript
        ]);
    }
}
 public function myjobs()
 {   
    $jobs=Job::where('user_id',Auth::user()->id)->with('jobType')->orderBy('created_at','DESC')->paginate(10);
    
    return view('front.account.job.my-jobs' ,[
        'jobs'=>$jobs
    ]);
 }
 public function editJob(Request $request,$id){
    
    $categories= Category :: orderBy('name','ASC')->where('status',1)->get();
       $jobTypes= JobType :: orderBy('name','ASC')->where('status',1)->get();

       $job=Job::where([
        'user_id'=> Auth::user()->id,
        'id'=> $id

       ])->first();
       //dd($job);

       if($job==null)
       {
           abort(404);
       }

    return view('front.account.job.edit',[
        'categories'=> $categories,
          'jobTypes'=>$jobTypes,
          'job'=>$job,

    ]);
 }

 public function updateJob(Request $request,$id)
 {
     $rules = [
         'title' => 'required|min:5|max:200',
         'category' => 'required',
         'jobType' => 'required',
         'vacancy' => 'required|integer',
         'location' => 'required|max:50',
         'description' => 'required',
         'company_name' => 'required|digits:11',
     ];
 
     $validator = Validator::make($request->all(), $rules);
 
     if ($validator->passes()) {
         $job =Job::find($id);
         $job->title= $request->title;
        // $categoryId = (int) $category->id;
         $job->category_id= $request->category;
         $job->job_type_id= $request->jobType;
         $job->user_id= Auth::user()->id;
         $job->vacancy= $request->vacancy;
         $job->salary= $request->salary;
         $job->location= $request->location;
         $job->description= $request->description;
         $job->benefits= $request->benefits;
         $job->responsibility= $request->responsibility;
         $job->qualifications= $request->qualifications;
         $job->experience= $request->experience;
         $job->keywords= $request->keywords;
         $job->company_name= $request->company_name;
         $job->company_location= $request->company_location;
         $job->company_website= $request->company_website;
         $job->save();
         
         session()->flash('success','Car updated Successfully');
 
         
         // Process the job creation
         return response()->json([
             'status' => true,
             'errors' => []
         ]);
     } else {
         return response()->json([
             'status' => false,
             'errors' => $validator->errors(), // Convert errors to array for easier handling in JavaScript
         ]);
     }
 }

 public function deleteJob(Request $request){

   $job= Job::where([
        'user_id'=> Auth::user()->id,
        'id'=>$request->jobId,

    ])->first();



       if($job==null)
       {
           session()->flash('error','Either car is deleted or not found.');
           return response()->json([
            'status'=>true
           ]);
       }

       Job::where('id',$request->jobId)->delete();

       session()->flash('success','Car deleted successfully.');
           return response()->json([
            'status'=>true
           ]);

 }


 public function savedJobs(){
    // $jobApplications = JobApplication::where('user_id',Auth::user()->id)
    //         ->with(['job','job.jobType','job.applications'])
    //         ->paginate(10);

    $savedJobs = SavedJob::where([
        'user_id' => Auth::user()->id
    ])->with(['job','job.jobType','job.applications'])
    ->orderBy('created_at','DESC')
    ->paginate(10);

    return view('front.account.job.saved-jobs',[
        'savedJobs' => $savedJobs
    ]);
}

public function removeSavedJob(Request $request){
    $savedJob = SavedJob::where([
                                'id' => $request->id, 
                                'user_id' => Auth::user()->id]
                            )->first();
    
    if ($savedJob == null) {
        session()->flash('error','car not found');
        return response()->json([
            'status' => false,                
        ]);
    }

    SavedJob::find($request->id)->delete();
    session()->flash('success','car removed successfully.');

    return response()->json([
        'status' => true,                
    ]);

}



 public function updatePassword(Request $request){

    $validator = Validator::make($request->all(),[
        'old_password'=>'required',
        'new_password'=>'required|min:5',
        'confirm_password'=>'required|same:new_password',
    ]);

    if($validator->fails()){
        return response()->json([
            'status'=>false,
            'errors'=>$validator->errors(),
        ]);
    }

    if(Hash::check($request->old_password,Auth::user()->password)==false){
        session()->flash('error','Your old password is incorrect.');
        return response()->json([
            'status'=>true,
           
        ]);

    }

    $user = User::find(Auth::user()->id);
     $user->password = Hash::make($request->new_password);
     $user->save();

     session()->flash('success','Password updated successfully.');
     return response()->json([
         'status'=>true,
        
     ]);


 }

 public function forgotPassword() {
    return view('front.account.forgot-password');
}

public function processForgotPassword(Request $request) {
    $validator = Validator::make($request->all(),[
        'email' => 'required|email|exists:users,email'
    ]);

    if ($validator->fails()) {
        return redirect()->route('account.forgotPassword')->withInput()->withErrors($validator);
    }

    $token = Str::random(60);

    \DB::table('password_reset_tokens')->where('email',$request->email)->delete();

    \DB::table('password_reset_tokens')->insert([
        'email' => $request->email,
        'token' => $token,
        'created_at' => now()
    ]);

    // Send Email here
    $user = User::where('email',$request->email)->first();
    $mailData =  [
        'token' => $token,
        'user' => $user,
        'subject' => 'You have requested to change your password.'
    ];

    Mail::to($request->email)->send(new ResetPasswordEmail($mailData));

    return redirect()->route('account.forgotPassword')->with('success','Reset password email has been sent to your inbox.');
    
}

public function resetPassword($tokenString) {
    $token = \DB::table('password_reset_tokens')->where('token',$tokenString)->first();

    if ($token == null) {
        return redirect()->route('account.forgotPassword')->with('error','Invalid token.');
    }

    return view('front.account.reset-password',[
        'tokenString' => $tokenString
    ]);
}


public function processResetPassword(Request $request) {

    $token = \DB::table('password_reset_tokens')->where('token',$request->token)->first();

    if ($token == null) {
        return redirect()->route('account.forgotPassword')->with('error','Invalid token.');
    }
    
    $validator = Validator::make($request->all(),[
        'new_password' => 'required|min:5',
        'confirm_password' => 'required|same:new_password',
    ]);

    if ($validator->fails()) {
        return redirect()->route('account.resetPassword',$request->token)->withErrors($validator);
    }

    User::where('email',$token->email)->update([
        'password' => Hash::make($request->new_password)
    ]);

    return redirect()->route('account.login')->with('success','You have successfully changed your password.');

}

    
}