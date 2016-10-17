<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers;
use Illuminate\Http\Request;

use \App\Models\Election;
use \App\Models\Position;
use \App\Models\User;

/**
 * Handles administrator actions concerning elections.
 *
 * @author  Jonas Dahl <jonas@jdahl.se>
 * @version 2016-10-14
 */
class ElectionAdminController extends BaseController {
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	/**
	 * Shows all elections as a list.
	 * 
	 * @return view containing a list over elections
	 */
	public function getShow() {
		$elections = Election::orderBy('opens', 'DESC')->paginate(20);
		return view('admin.elections.index')->with('elections', $elections);
	}

	/**
	 * Show form for adding a new election.
	 * 
	 * @return view containing new election form
	 */
	public function getNew() {
		return view('admin.elections.new');
	}

	/**
	 * Show form for editing an election. Returns 400 if $id not found
	 * 
	 * @param  int $id the id of the election to edit
	 * @return view    the form view
	 */
	public function getEdit($id) {
		$election = Election::find($id);
		if ($election === null) {
			abort(400);
		}
		return view('admin.elections.edit')->with('election', $election)->with('positions', $election->positions());
	}

	/**
	 * Handles post request on creating a new election.
	 * 
	 * @param  Request $request the post request
	 * @return redirect         to /admin/elections on success, back otherwise
	 */
	public function postNew(Request $request) {
		$this->validate($request, [
			'name' 				=> 'required',
			'description' 		=> 'required',
			'opens' 			=> 'required|date',
			'nomination_stop' 	=> 'required|date|after:opens',
			'acceptance_stop' 	=> 'required|date|after:nomination_stop',
			'closes' 			=> 'required|date|after:acceptance_stop',
			'positions' 		=> 'required|array|minCount:1'
		]);

		$election = new Election;
		$election->name = $request->input('name');
		$election->description = $request->input('description');
		$election->opens = $request->input('opens');
		$election->closes = $request->input('closes');
		$election->acceptance_stop = $request->input('acceptance_stop');
		$election->nomination_stop = $request->input('nomination_stop');
		$election->save();

		// Connect all positions to this election
		foreach ($request->get('positions') as $positionIdentifier) {
			$election->addPosition($positionIdentifier);
		}

		return redirect('/admin/elections');
	}

	/**
	 * Handles post request on editing an election.
	 * 
	 * @param  ind     $id      the id to edit
	 * @param  Request $request the post request
	 * @return redirect         to /admin/election is succes, back otherwise
	 */
	public function postEdit($id, Request $request) {
		$this->validate($request, [
			'name' 				=> 'required',
			'description' 		=> 'required',
			'opens' 			=> 'required|date',
			'nomination_stop' 	=> 'required|date|after:opens',
			'acceptance_stop' 	=> 'required|date|after:nomination_stop',
			'closes' 			=> 'required|date|after:acceptance_stop',
			'positions' 		=> 'required|array|minCount:1'
		]);

		// Get the election from $id, or die
		$election = Election::find($id);
		if ($election === null) {
			return redirect()->back()->withInput()->with('error', 'Valtillfället kunde inte hittas.');
		}

		// Save new information
		$election->name = $request->input('name');
		$election->description = $request->input('description');
		$election->opens = $request->input('opens');
		$election->closes = $request->input('closes');
		$election->acceptance_stop = $request->input('acceptance_stop');
		$election->nomination_stop = $request->input('nomination_stop');
		$election->save();

		// First disconnect all the positions in case we already are connected 
		$election->removeAllPositions();

		// Connect all positions to this election
		foreach ($request->get('positions') as $positionIdentifier) {
			$election->addPosition($positionIdentifier);
		}

		return redirect('/admin/elections')->with('success', 'Valet uppdaterades.');
	}

	/**
	 * Remove nomination. Asks for comfirmation.
	 * 
	 * @param  string $uuid the uuid to remove
	 * @return view to show question
	 */
	public static function getRemoveNomination($uuid) {
		// Get the nomination row
		$row = \DB::table('position_user')
			->where('uuid', $uuid)
			->first();

		// Redirect away if bad
		if ($row === null) {
			return redirect('/')->with('error', 'Kunde inte hitta nominering att ta bort.');
		}

		return view('admin.elections.remove-nomination')
			->with('nomination', $row)
			->with('uuid', $uuid);
	}

	/**
	 * Remove nomination.
	 * 
	 * @param  string $uuid the uuid to remove
	 * @return redirect     to main page
	 */
	public static function getRemoveNominationSure($uuid) {
		// Get the nomination row
		$row = \DB::table('position_user')
			->where('uuid', $uuid)
			->first();

		if ($row === null) {
			return redirect('/')->with('error', 'Kunde inte hitta nominering att ta bort.');
		}

		// Do the delete
		\DB::table('position_user')
			->where('uuid', $uuid)
			->delete();

		return redirect('/')->with('success', 'Tog bort nominering.');
	}
}
