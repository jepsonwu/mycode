#include <stdio.h>
#include <stdlib.h>
#include <stdbool.h>

typedef struct node *position;
typedef struct node *stack;
typedef int ElementTP;//自定义栈类型，方便扩展

struct node {
	ElementTP element;
	position next;
};

stack init_stack(void);
void push(stack sk,ElementTP element);
ElementTP pop(stack sk);
ElementTP top(stack sk);
void delete(stack sk);
int is_null(stack sk);

//用链表实现栈
void main(){
	stack sk;
	sk=init_stack();

	int arr[5]={5,9,6,3,2};
	int *ar=&arr[0];
	int i;

	for (i = 0; i < 5; ++i)
	{
		push(sk,*(ar++));
	}

	ElementTP element;
	for (i = 0; i < 2; ++i)
	{
		element=pop(sk);
		element!=false&&printf("%d\n",element);
	}

	delete(sk);
	//printf("%d\n",top(sk));
	//printf("%p\n",sk);
}

/**
 * 初始化栈 null 不能作为指针传递,所以这里用返回值
 * @return  [description]
 */
stack init_stack(void){
	stack sk;

	sk=(stack)malloc(sizeof(stack));
	sk->next=NULL;

	return sk;
}

/**
 * 入栈  注意  是把当前的插入到链表的最头端  head->now->old
 * @param sk      [description]
 * @param element [description]
 */
void push(stack sk,ElementTP element){
	position np,old;
	old=sk->next;

	np=(position)malloc(sizeof(struct node));
	np->next=old;
	np->element=element;

	sk->next=np;
}

/**
 * 出栈
 * @param  sk [description]
 * @return    [description]
 */
ElementTP pop(stack sk){
	if(is_null(sk))
		return false;//(is_null(sk)==1)&&return false;函数支持这样的写法，变量可以

	ElementTP element;
	position np;
	np=sk->next;

	element=np->element;
	sk->next=np->next;
	free(np);

	return element;
}

/**
 * top
 * @param  sk [description]
 * @return    [description]
 */
ElementTP top(stack sk){
	return sk->next->element;
}

/**
 * 删除栈
 * @param sk [description]
 */
void delete(stack sk){
	while(!is_null(sk)){
		pop(sk);
	}

	free(sk);
}

/**
 * 判断是否为空
 * @param  sk [description]
 * @return    [description]
 */
int is_null(stack sk){
	return (sk->next==NULL);
}