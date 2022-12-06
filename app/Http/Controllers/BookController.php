<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{

    public function BookController()
    {
    }

    public function index()
    {
        $books = Book::with('authors', 'category', 'editorial')->get();
        return [
            "error" => false,
            "message" => "Succesfull query",
            "data" => $books
        ];
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $existIsbn = Book::where('isbn', trim($request->isbn))->exists();
            if (!$existIsbn) {
                $book = new Book();
                $book->isbn = trim($request->isbn);
                $book->title = $request->title;
                $book->description = $request->description;
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial_id;
                $book->publish_date = Carbon::now();
                $book->save();
               
                foreach ($request->authors as $item) {
                    $book->authors()->attach($item);
                }
                $bookId = $book->id;
                return [
                    "status" => true,
                    "message" => "Your book has been created!",
                    "data" => [
                        "book_id" => $bookId,
                        "book" => $book
                    ]
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "The ISBN already exists!",
                    "data" => []
                ];
            }
            DB::commit(); //Save all
        } catch (Exception $e) {
            DB::rollBack(); //Discard changes
            return [
                "status" => true,
                "message" => "Wrong operation!",
                "data" => []
            ];
        }
    }

    public function update(Request $request, $id)
    {
        $response = $this->getResponse();
        DB::beginTransaction();
        try {
            $book = Book::find($id);
            if ($book) {
                $isbnOwner = Book::where("isbn", $request->isbn)->first();
                if (!$isbnOwner || $isbnOwner->id == $book->id) {
                    $book->isbn = trim($request->isbn);
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->category_id = $request->category["id"];
                    $book->editorial_id = $request->editorial_id;
                    $book->publish_date = Carbon::now();
                    $book->update();
                    //Delete
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item->id);
                    }
                    //Add
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category', 'editorial', 'authors')->where("id", $id)->get();
                    $response["error"] = false;
                    $response["message"] = "Your book has been updated!";
                    $response["data"] = $book;
                } else {
                    $response["message"] = "ISBN duplicated";
                }
            } else {
                $response["message"] = "Not found";
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack(); //Discard changes   
        }
        return $response;
    }

    public function show($id)
    {
        $response = $this->getResponse();
        $book = Book::with('category', 'editorial', 'authors')->where("id", $id)->get();
        if ($book) {
            $response["error"] = false;
            $response["message"] = "Successfull query!";
            $response["data"] = $book;
        } else {
            $response["message"] = "Not found";
        }
        return $response;
    }

    public function destroy($id)
    {
        $response = $this->getResponse();
        $book = Book::find($id);
        if ($book) {
            foreach ($book->authors as $item) {
                $book->authors()->detach($item->id);
            }
            $book->delete();
            $response["error"] = false;
            $response["message"] = "Your book has been removed!";
            $response["data"] = $book;
        } else {
            $response["message"] = "Not found";
        }
        return $response;
    }
}
