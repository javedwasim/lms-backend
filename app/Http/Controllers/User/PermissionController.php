<?php
    
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
    
class PermissionController extends Controller
{ 
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permissions = Permission::latest()->paginate(5);
        return view('permission.index',compact('permissions'))
            ->with('i', (request()->input('page', 1) - 1) * 5);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('permission.create');
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'name' => 'required', 
        ]);
    
        Permission::create($request->all());
    
        return redirect()->route('permission.index')
                        ->with('success','Permission created successfully.');
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Permission  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        return view('permission.show',compact('permission'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Permission  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    { 
        return view('permission.edit',compact('permission'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Permission  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
         request()->validate([
            'name' => 'required', 
        ]);
        $input = $request->all();
        
        $user = Permission::find($id)->update($input);

        return redirect()->route('permission.index')
                        ->with('success','Permission updated successfully');
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Permission  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Permission::find($id)->delete();
    
        return redirect()->route('permission.index')
                        ->with('success','Permission deleted successfully');
    }
}