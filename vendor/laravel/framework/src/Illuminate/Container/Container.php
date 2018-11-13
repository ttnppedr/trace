<?php

namespace Illuminate\Container;

use Closure;
use Exception;
use ArrayAccess;
use LogicException;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container implements ArrayAccess, ContainerContract
{
    /**
     * The current globally available container (if any).
     * 當下全域的容器
     *
     * @var static
     */
    protected static $instance;

    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's method bindings.
     *
     * @var array
     */
    protected $methodBindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array
     */
    protected $abstractAliases = [];

    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected $extenders = [];

    /**
     * All of the registered tags.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * The parameter override stack.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The contextual binding map.
     *
     * @var array
     */
    public $contextual = [];

    /**
     * All of the registered rebound callbacks.
     *
     * @var array
     */
    protected $reboundCallbacks = [];

    /**
     * All of the global resolving callbacks.
     * 全域 callback
     *
     * @var array
     */
    protected $globalResolvingCallbacks = [];

    /**
     * All of the global after resolving callbacks.
     * 解析後的 callback
     *
     * @var array
     */
    protected $globalAfterResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type.
     *
     * @var array
     */
    protected $resolvingCallbacks = [];

    /**
     * All of the after resolving callbacks by class type.
     *
     * @var array
     */
    protected $afterResolvingCallbacks = [];

    /**
     * Define a contextual binding.
     *
     * @param  array|string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        $aliases = [];

        foreach (Arr::wrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Determine if the given abstract type has been bound.
     * 檢查傳入的 abstract type 是否已被綁定
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        // 代表如果在 bindings, instances, aliases 這三個 array 其一存在
        // 就會被認定為是已被綁定
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     *  {@inheritdoc}
     */
    public function has($id)
    {
        return $this->bound($id);
    }

    /**
     * Determine if the given abstract type has been resolved.
     * 判斷 abstract 是否已被解析
     *
     * @param  string  $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        // 如果已在 aliases 中的話，拿出真正的 alias
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        // 如果在 resolved 或 instances 中存在其一的話，代表已被解析
        // resolved 在 resolve() 中會被放進去
        // instances 在 instance() 中會被放進去
        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     * 是否已被分享
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        // 是否已被分享需判斷 instances 或 bindings 的 shared 是否為 true
        return isset($this->instances[$abstract]) ||
              (isset($this->bindings[$abstract]['shared']) &&
               $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
     * 檢查傳入的 string 是否為 alias 之一
     *
     * @param  string  $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     * 註冊綁定在 container 中
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.

        // 如果 type 不是 concrete ， 我們直接將 concrete 設為與 abstract 相同
        // 之後 concrete 會被註冊為 shared ， 不會在參數之中強制設定狀態
        // 拿掉 instances 及 aliases
        $this->dropStaleInstances($abstract);

        // 如果 concrete 沒帶入的話，將之設為與 abscract 相同
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.

        // 待了解 factory 代表什麼
        // 如果不是 Closure ，這代表只是一個被綁定此容器 abstract type 的 class 名字而已
        // 我們會將之包進它本身的 Closure ，當需要擴充時，使我們可以更便利
        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        // 到此 concrete 已是一個 Closure

        $this->bindings[$abstract] = compact('concrete', 'shared');

        // bindings 中是一個 array 放著 Closure 的 concrete 及 boolean 的 shared

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.

        // 如果 abstract type 在此容器已被解析，我們將觸發重新綁定
        // 這樣所有已解析的 object 複本，可以透過 callback 來更新
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
     * 取得 Closure 來創建 type
     *
     * @param  string  $abstract
     * @param  string  $concrete
     * @return \Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                // 拿實例
                return $container->build($concrete);
            }

            // 解析
            return $container->make($concrete, $parameters);
        };
    }

    /**
     * Determine if the container has a method binding.
     *
     * @param  string  $method
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param  array|string  $method
     * @param  \Closure  $callback
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
     *
     * @param  array|string $method
     * @return string
     */
    protected function parseBindMethod($method)
    {
        if (is_array($method)) {
            return $method[0].'@'.$method[1];
        }

        return $method;
    }

    /**
     * Get the method binding for the given method.
     *
     * @param  string  $method
     * @param  mixed  $instance
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
     *
     * @param  string  $concrete
     * @param  string  $abstract
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     * 在 container 中註冊 shared 的綁定
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;

            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }

    /**
     * Register an existing instance as shared in the container.
     * 註冊已經存在的實例讓他們在容器中被分享
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        // $abstract 為字串名稱，如 path
        // $instance 為實際路徑，如 /Users/ttnppedr/code/trace/app

        // 移除抽象 alias
        // Application 中的 bindPathsInContainer ，不會有作用
        $this->removeAbstractAlias($abstract);

        // 檢查是否已被綁定
        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.

        // 我們會檢查這個實例是否曾經被綁定過，如果是，將會觸發重新綁定的 callbacks ，在容器中，
        // 因此已解析的消耗性 classes 得以被更新

        // 放進 instances array 中
        // 型式 [$abstract => $instance]
        // 型式 ['path' => '/Users/ttnppedr/code/trace/app']
        $this->instances[$abstract] = $instance;

        // 若已被綁定則重新綁定
        if ($isBound) {
            // 重新綁定
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     * 從文字綁定的 alias cache 中移除一個 alias
     *
     * @param  string  $searched
     * @return void
     */
    protected function removeAbstractAlias($searched)
    {
        // 如果不存在 aliases 中則不做事
        // Application 中的 bindPathsInContainer 全都會進這個條件
        // 代表那時全都還沒有存進 aliases
        if (! isset($this->aliases[$searched])) {
            return;
        }

        // 如果存在，則移除
        // $abstractAliases[$abstract[$index=>$alias]]
        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Assign a set of tags to a given binding.
     *
     * @param  array|string  $abstracts
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract);
            }
        }

        return $results;
    }

    /**
     * Alias a type to a different name.
     * 放進 alias abstract 對應 array
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        // 這代表會把 abstract 跟 alias 的對應關係，用 aliases 及 abstractAliases 記住
        // aliases 為一筆一筆的記，範例型式如下：
        // $aliases = [
        //   'Illuminate\Foundation\Application' => 'app',
        //   'Illuminate\Contracts\Container\Container' => 'app',
        //   'Illuminate\Auth\AuthManager' => 'auth'
        //   ...
        //   ]
        // abstractAliases 會分組的記，範例型式如下：
        // $abstractAliases = [
        //   'app' => [
        //     'Illimunate\Foundation\Application',
        //     'Illuminate\Contracts\Container\Container'
        //   ],
        //   'auth' => [
        //     'Illuminate\Auth\AuthManager',
        //   ]
        //   ...
        // ]
        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * @param  string    $abstract
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rebinding($abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
    }

    /**
     * Refresh an instance on the given target and method.
     *
     * @param  string  $abstract
     * @param  mixed   $target
     * @param  string  $method
     * @return mixed
     */
    public function refresh($abstract, $target, $method)
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     * 觸發 abstract type 重新綁定的 callback
     *
     * @param  string  $abstract
     * @return void
     */
    protected function rebound($abstract)
    {
        // 解析 resolver
        $instance = $this->make($abstract);

        // 觸發重新綁定的 callback
        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            // 觸發
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     * 獲取重新綁定的 callback
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getReboundCallbacks($abstract)
    {
        if (isset($this->reboundCallbacks[$abstract])) {
            return $this->reboundCallbacks[$abstract];
        }

        return [];
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param  \Closure  $callback
     * @param  array  $parameters
     * @return \Closure
     */
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * An alias function name for make().
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function makeWith($abstract, array $parameters = [])
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     * 從容器解析傳入的 type
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        // 解析
        return $this->resolve($abstract, $parameters);
    }

    /**
     *  {@inheritdoc}
     */
    public function get($id)
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }

            throw new EntryNotFoundException;
        }
    }

    /**
     * Resolve the given type from the container.
     * 解析從容器傳入的 type
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [])
    {
        // 取得 alias
        $abstract = $this->getAlias($abstract);

        // 傳入的參數非空或有 contextual concrete
        $needsContextualBuild = ! empty($parameters) || ! is_null(
            $this->getContextualConcrete($abstract)
        );

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.

        // 如果實例已被當作 singleton 管理，直接回傳現有的實例，不需要再重新實例化，確保開發者
        // 每次都可以得到同一個實例
        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        // 把參數放進 with
        $this->with[] = $parameters;

        // 拿可解析的 concrete
        $concrete = $this->getConcrete($abstract);

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.

        // 已經準備好實例化 concrete type 已註冊的綁定，這會實例化這個 type 本身，且也會解析所有相關的巢狀相依
        // 遞迴地執行直到所有都被解析完為止
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            // make 之中就是 resolve，等於遞迴執行
            $object = $this->make($concrete);
        }

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.

        // 如果有任何的 extender ，我們需要旋轉一下，並套用到正在創建的物件
        // 這允許額外的 service ，比如改變設定或是裝飾物件
        // 待了解 extender 使用時機
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.

        // 如果請求的 type 已經註冊為 singleton ，我們想要快取這個實例在記憶體中
        // 如果後續還有需要的話，這樣我們就不需要再創建一次

        // 這邊如果是已被分享的且不需要額外的 contextual build ，則直接更新在 instances
        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        // 觸發 callback
        $this->fireResolvingCallbacks($abstract, $object);

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.

        // 在回傳之前，還需要將此設定為已解析並將這次創建的參數扔出
        // 這兩件事做完後，才可以回傳完整建構的實例
        $this->resolved[$abstract] = true;

        // 將前面放進去的參數丟掉
        array_pop($this->with);

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     * 獲取傳入 abstract 的 concrete type
     *
     * @param  string  $abstract
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        // 找到 contextual concrete
        if (! is_null($concrete = $this->getContextualConcrete($abstract))) {
            return $concrete;
        }

        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.

        // 如果沒有註冊的解析器或是 concrete type ，將會認定所有的 type 都是 concrete，
        // 試著去解析，因為容器應該可以自動地解析 concrete
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     * 獲取傳入abstract 的 concrete 的綁定
     *
     * @param  string  $abstract
     * @return string|null
     */
    protected function getContextualConcrete($abstract)
    {
        // 如果有在 contextual bindings 之中
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.

        // 接下來我們需要確認 contextual binding 是否可能被綁定在abstract 的 alias 底下
        // 所以我們需要確認這個 type 是否存在任何 aliases ，轉動一下他們確認有沒有在這之中
        if (empty($this->abstractAliases[$abstract])) {
            return;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (! is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     * 傳入 abstract 找 concrete 綁定是否已在 contextual binding array 之中
     *
     * @param  string  $abstract
     * @return string|null
     */
    protected function findInContextualBindings($abstract)
    {
        // 待了解 contextual 及 buildStack 在何時會被寫入
        if (isset($this->contextual[end($this->buildStack)][$abstract])) {
            return $this->contextual[end($this->buildStack)][$abstract];
        }
    }

    /**
     * Determine if the given concrete is buildable.
     * 是否可 build
     *
     * @param  mixed   $concrete
     * @param  string  $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        // abstract 就是 concrete 本身或是 concrete 是一個 Closure
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     * 實例化傳入的 concrete
     *
     * @param  string  $concrete
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.

        // 如果 concrete type 是一個 Closure ，就直接執行並回傳執行結果，
        // Closure 在這邊可以讓解析器做一些微調
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        $reflector = new ReflectionClass($concrete);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.

        // 如果 type 沒辦法實例化，開發者試著去解析一個像 Abstract Class 的 abstract type
        // 因為沒辦法對他註冊綁定，所以就需要讓他失敗
        if (! $reflector->isInstantiable()) {
            // 連之前的 buildStack 一起丟 exception
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.

        // 如果沒有 consturctor ，代表沒有相依，就可以立即解析，不用再考慮是否還其他相依需要解析的 type
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.

        // 一但我們有了 constructor 的參數，我們可以創建所有相依的實例
        // 用這些 reflection 的實例去那些真的實例，並 inject 這些已經創建好的相依
        $instances = $this->resolveDependencies(
            $dependencies
        );

        // 丟出 buildStack
        array_pop($this->buildStack);

        // 回傳這個實例
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     * 解析所有 ReflectionParameters 的相依
     *
     * @param  array  $dependencies
     * @return array
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.

            // 如果這個相依已經在這次的 build 中已經被創建的話，我們就直接拿來用
            // 否則我們會繼續跑下去，讓 reflection 試著去決定最後的結果
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.

            // 如果相依沒有任何的 class，這意謂著相依只是個字串或是其他基本型態
            // 因為不是 class 所以我們沒辦法解析
            // 我們就只能任由其錯誤炸開，因為別無他法
            $results[] = is_null($dependency->getClass())
                            ? $this->resolvePrimitive($dependency)
                            : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Determine if the given dependency has a parameter override.
     * 決定傳入的相依是否已存在
     *
     * @param  \ReflectionParameter  $dependency
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     * 取相依已存在的參數
     *
     * @param  \ReflectionParameter  $dependency
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        // 放進最後一個參數之中
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Get the last parameter override.
     * 取得最後一個參數
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        // 有參數就回傳最後一個，否則回傳空 array
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     * 解析非 class 的基本型態相依
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        // 如果有 contextual concrete 且是 Closure 就執行，否則回傳
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->name))) {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }

        // 檢查是否有預設值
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // 丟 exception
        $this->unresolvablePrimitive($parameter);
    }

    /**
     * Resolve a class based dependency from the container.
     * 解析 class 的相依
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        }

        // If we can not resolve the class instance, we will check to see if the value
        // is optional, and if it is we will return the optional parameter value as
        // the value of the dependency, similarly to how we do this with scalars.

        // 如果無法解析，我們需要檢查是否為選填，如果是的話，我們就回傳一個選擇性的值當作是這個相依的值
        // 就像我們對待純量那樣
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     * 丟出 concrete 沒辦法實例化的 exception
     *
     * @param  string  $concrete
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function notInstantiable($concrete)
    {
        // 若已經有 build 的 stack，一起回傳
        if (! empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     * 丟一個無法解析基本型態的 exception
     *
     * @param  \ReflectionParameter  $parameter
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Register a new resolving callback.
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all of the resolving callbacks.
     * 觸發所有解析中的 callback
     *
     * @param  string  $abstract
     * @param  mixed   $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        // 全域 callback
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        // 可執行的 callback
        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        // 解析後
        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all of the after resolving callbacks.
     * 觸發所有解析後的 callback
     *
     * @param  string  $abstract
     * @param  mixed   $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        // 全域
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        // 可執行
        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     * 取得所有 type 的 callback
     *
     * @param  string  $abstract
     * @param  object  $object
     * @param  array   $callbacksPerType
     *
     * @return array
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];

        // 過濾可以執行的 callback
        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     * 執行 object 的 callback
     *
     * @param  mixed  $object
     * @param  array  $callbacks
     * @return void
     */
    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
     * 回傳 abstract 的 alias
     *
     * @param  string  $abstract
     * @return string
     *
     * @throws \LogicException
     */
    public function getAlias($abstract)
    {
        // 沒有在 aliases 之中，直接回傳
        if (! isset($this->aliases[$abstract])) {
            return $abstract;
        }

        // abstract 自己本身就是 alias
        // 待了解為什麼會出現兩者相同的情況，如下：
        // $this->aliases["Illuminate\Contracts\Http\Kernel"] = "Illuminate\Contracts\Http\Kernel";
        if ($this->aliases[$abstract] === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        // 遞迴再往下一層拿 alias
        // 待了解為什麼會出現多層的 aliases 的情況
        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Get the extender callbacks for a given type.
     * 取得 extender 的 callbacks
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getExtenders($abstract)
    {
        $abstract = $this->getAlias($abstract);

        // 待了解 extenders 的修改時機
        if (isset($this->extenders[$abstract])) {
            return $this->extenders[$abstract];
        }

        return [];
    }

    /**
     * Remove all of the extender callbacks for a given type.
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetExtenders($abstract)
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }

    /**
     * Drop all of the stale instances and aliases.
     * 移除所有過期沒用的 instances 及 aliases
     *
     * @param  string  $abstract
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return \Illuminate\Contracts\Container\Container|static
     */
    public static function setInstance(ContainerContract $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
