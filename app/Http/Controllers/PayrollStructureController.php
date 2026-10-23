<?php
namespace App\Http\Controllers;
use App\Models\Department;
use Illuminate\Http\Request;
class PayrollStructureController extends Controller
{
 public function department(Request $r){$d=$r->validate(['name'=>['required','string','max:100','unique:departments,name']]);Department::create($d);return back()->with('success','Department added.');}
 public function position(Request $r){$d=$r->validate(['department_id'=>['required','exists:departments,id'],'title'=>['required','string','max:100']]);$exists=\App\Models\JobPosition::where('department_id',$d['department_id'])->where('title',$d['title'])->exists();if($exists)return back()->withErrors(['title'=>'That position already exists in the selected department.']);\App\Models\JobPosition::create($d);return back()->with('success','Job position added.');}
}
