<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\JobType;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class JobsController extends Controller
{
    //this method will show job page
    public function index (Request $request){

        $categories= Category :: where('status',1)->get();
        $jobTypes= JobType :: where('status',1)->get();

        $jobs=Job::where('status',1);


        //search using keywords
        if (!empty ($request->keyword)){
            $jobs = $jobs->where(function($query) use ($request){
                $query->orWhere('title','like','%'.$request->keyword.'%');
                $query->orWhere('keywords','like','%'.$request->keyword.'%');

            });
        }

        //search using location

        if(!empty($request->location)){
            $jobs = $jobs->where('location',$request->location);
        }

        //search using category

        if(!empty($request->category)){
            $jobs = $jobs->where('category_id',$request->category);
        }

        $jobTypeArray=[];

         //search using jobType

         if(!empty($request->jobType)){
            //1,2,3

            $jobTypeArray = explode(',',$request->jobType);


            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }


         //search using experience

         if(!empty($request->experience)){
            $jobs = $jobs->where('experience',$request->experience);
        }

        $jobs = $jobs->with(['jobType','category']);
        if($request->sort=='0')
        {
            $jobs=$jobs->orderBy('created_at','ASC');

        }else{
            $jobs=$jobs->orderBy('created_at','DESC');
        }
        $jobs=$jobs->paginate(10);




        return view('front.jobs',[
            'categories'=> $categories,
            'jobTypes'=>$jobTypes,
            'jobs'=>$jobs,
            'jobTypeArray'=>$jobTypeArray
        ]);

    }

    //this method will show the job detail page
    public function detail($id){

        $job = Job::where([
            'id' => $id,
            'status' => 1

        ])->with(['jobType','category'])->first();
        
        if($job == null)
        {
            abort(404);
        }

        $count = 0;
        if (Auth::user()) {
            $count = SavedJob::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id
            ])->count();
        }
        
        return view('front.jobDetail',['job' => $job, 'count' => $count]);
       
    }

    public function saveJob(Request $request) {

        $id = $request->id;

        $job = Job::find($id);

        if ($job == null) {
            session()->flash('error','Car not found');

            return response()->json([
                'status' => false,
            ]);
        }

        // Check if user already saved the job
        $count = SavedJob::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        if ($count > 0) {
            session()->flash('error','You already saved this Car.');

            return response()->json([
                'status' => false,
            ]);
        }

        $savedJob = new SavedJob;
        $savedJob->job_id = $id;
        $savedJob->user_id = Auth::user()->id;
        $savedJob->save();

        session()->flash('success','You have successfully saved the Car.');

        return response()->json([
            'status' => true,
        ]);

    }
}
