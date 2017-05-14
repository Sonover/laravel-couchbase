<?php


namespace Sononver\Couchbase;


use Illuminate\Auth\GenericUser;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CouchbaseUserProvider implements UserProvider
{
    /**
     * @var ConnectionInterface
     */
    private $conn;
    /**
     * @var HasherContract
     */
    private $hasher;
    /**
     * @var
     */
    private $table;
    /**
     * @var
     */
    private $bucket;

    /**
     * CouchbaseUserProvider constructor.
     * @param ConnectionInterface $conn
     * @param HasherContract $hasher
     */
    public function __construct(ConnectionInterface $conn, HasherContract $hasher, $bucket)
    {
        $this->conn = $conn;
        $this->hasher = $hasher;
        $this->bucket = $bucket;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $bucket = $this->conn->openBucket($this->bucket);
        try {
            $user = $bucket->get($identifier);

            return $this->getGenericUser(array_merge(json_decode($user->value, true), ['id' => $identifier]));

        }catch (\Exception $e)
        {
            return null;
        }
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $query = $this->conn->table($this->bucket);

        $query->select($this->bucket.'.*, meta().id')->from($this->bucket);

        foreach ($credentials as $key => $value) {
            if (! Str::contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        $query->where('type', 'user');

        // Now we are ready to execute the query to see if we have an user matching
        // the given credentials. If not, we will just return nulls and indicate
        // that there are no matching users for these given credential arrays.
        $user = $query->first();

        return $this->getGenericUser($user);

    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if ($user !== null) {
            return new GenericUser((array) $user);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }


}