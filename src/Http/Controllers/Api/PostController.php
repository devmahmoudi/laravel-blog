<?php

namespace DevMahmoudi\Blog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DevMahmoudi\Blog\Models\Post;
use DevMahmoudi\Blog\Http\Resources\PostResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * List all published blog posts
     * 
     * Returns a paginated list of published blog posts with optional search and sorting.
     * 
     * @group Blog Posts
     * @subgroup Public Endpoints
     * 
     * @queryParam search string Search posts by title, content, or excerpt. Example: laravel
     * @queryParam sort_by string Field to sort by (title, published_at, created_at). Default: published_at. Example: title
     * @queryParam sort_direction string Sort direction (asc, desc). Default: desc. Example: asc
     * @queryParam per_page int Number of posts per page (max 100). Default: 15. Example: 10
     * 
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Getting Started with Laravel",
     *       "slug": "getting-started-with-laravel",
     *       "excerpt": "Learn Laravel basics...",
     *       "published_at": "2024-01-01T00:00:00Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://example.com/api/blog/posts?page=1",
     *     "last": "http://example.com/api/blog/posts?page=3",
     *     "prev": null,
     *     "next": "http://example.com/api/blog/posts?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 3,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 45
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Post::query()->published();

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $sortField = $request->get('sort_by', 'published_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $allowedSortFields = ['title', 'published_at', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        
        return PostResource::collection($query->paginate($perPage));
    }

    /**
     * Get a specific blog post
     * 
     * Returns detailed information about a single blog post by its slug.
     * 
     * @group Blog Posts
     * @subgroup Public Endpoints
     * 
     * @urlParam post string required The slug of the post. Example: getting-started-with-laravel
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "title": "Getting Started with Laravel",
     *     "slug": "getting-started-with-laravel",
     *     "content": "Full content here...",
     *     "excerpt": "Learn Laravel basics...",
     *     "featured_image": "https://example.com/image.jpg",
     *     "is_published": true,
     *     "published_at": "2024-01-01T00:00:00Z",
     *     "meta_title": "Laravel Tutorial",
     *     "meta_description": "Complete Laravel guide",
     *     "created_at": "2024-01-01T00:00:00Z",
     *     "updated_at": "2024-01-01T00:00:00Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "No query results for model [DevMahmoudi\\Blog\\Models\\Post]."
     * }
     */
    public function show(Post $post): PostResource
    {
        if (!$post->is_published && !request()->user()?->can('view unpublished posts')) {
            abort(404);
        }

        return new PostResource($post);
    }

    /**
     * Create a new blog post
     * 
     * Creates a new blog post. Requires authentication.
     * 
     * @group Blog Posts
     * @subgroup Admin Endpoints
     * @authenticated
     * 
     * @bodyParam title string required The title of the post. Example: Getting Started with Laravel
     * @bodyParam slug string required Unique URL-friendly slug. Example: getting-started-with-laravel
     * @bodyParam content string required The full content of the post. Example: # Introduction\n\nThis is a tutorial...
     * @bodyParam excerpt string Short description of the post (max 500 chars). Example: Learn Laravel basics in this tutorial
     * @bodyParam featured_image string URL to the featured image. Example: https://example.com/image.jpg
     * @bodyParam is_published boolean Whether to publish immediately. Default: false. Example: true
     * @bodyParam published_at string ISO 8601 date when to publish. Example: 2024-01-01T00:00:00Z
     * @bodyParam meta_title string SEO meta title. Example: Laravel Tutorial for Beginners
     * @bodyParam meta_description string SEO meta description. Example: Comprehensive Laravel guide
     * 
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "title": "Getting Started with Laravel",
     *     "slug": "getting-started-with-laravel",
     *     "content": "# Introduction\n\nThis is a tutorial...",
     *     "excerpt": "Learn Laravel basics",
     *     "published_at": "2024-01-01T00:00:00Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "title": ["The title field is required."],
     *     "slug": ["The slug has already been taken."]
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|string|max:2048',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $post = Post::create($validated);

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing blog post
     * 
     * Updates the specified blog post. Requires authentication.
     * 
     * @group Blog Posts
     * @subgroup Admin Endpoints
     * @authenticated
     * 
     * @urlParam post string required The slug of the post to update. Example: getting-started-with-laravel
     * @bodyParam title string The new title. Example: Updated Laravel Guide
     * @bodyParam content string The new content. Example: Updated content...
     * @bodyParam is_published boolean Publish status. Example: false
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "title": "Updated Laravel Guide",
     *     "slug": "getting-started-with-laravel",
     *     "content": "Updated content..."
     *   }
     * }
     */
    public function update(Request $request, Post $post): PostResource
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:blog_posts,slug,' . $post->id,
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|string|max:2048',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $post->update($validated);

        return new PostResource($post->fresh());
    }

    /**
     * Delete a blog post
     * 
     * Deletes the specified blog post permanently. Requires authentication.
     * 
     * @group Blog Posts
     * @subgroup Admin Endpoints
     * @authenticated
     * 
     * @urlParam post string required The slug of the post to delete. Example: getting-started-with-laravel
     * 
     * @response {
     *   "message": "Post deleted successfully"
     * }
     * 
     * @response 404 {
     *   "message": "No query results for model [DevMahmoudi\\Blog\\Models\\Post]."
     * }
     */
    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Get recent blog posts
     * 
     * Returns the most recent published posts. Useful for sidebars or featured sections.
     * 
     * @group Blog Posts
     * @subgroup Public Endpoints
     * 
     * @queryParam limit int Maximum number of posts to return (max 20). Default: 5. Example: 3
     * 
     * @response {
     *   "data": [
     *     {
     *       "id": 5,
     *       "title": "Latest Post",
     *       "slug": "latest-post",
     *       "published_at": "2024-01-05T00:00:00Z"
     *     },
     *     {
     *       "id": 4,
     *       "title": "Previous Post",
     *       "slug": "previous-post",
     *       "published_at": "2024-01-04T00:00:00Z"
     *     }
     *   ]
     * }
     */
    public function recent(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->get('limit', 5), 20);
        
        $posts = Post::published()
            ->latest('published_at')
            ->take($limit)
            ->get();

        return PostResource::collection($posts);
    }
}